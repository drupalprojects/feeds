<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Fetcher\UploadFetcher.
 */

namespace Drupal\feeds\Plugin\feeds\Fetcher;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedPluginFormInterface;
use Drupal\feeds\FetcherResult;
use Drupal\feeds\Plugin\FetcherBase;

/**
 * Defines a file upload fetcher.
 *
 * @Plugin(
 *   id = "upload",
 *   title = @Translation("Upload fetcher"),
 *   description = @Translation("Upload content from a local file.")
 * )
 */
class UploadFetcher extends FetcherBase implements FeedPluginFormInterface, FormInterface {

  /**
   * {@inheritdoc}
   */
  public function fetch(FeedInterface $feed) {
    $feed_config = $feed->getConfigurationFor($this);

    if (is_file($feed_config['source'])) {
      return new FetcherResult($feed_config['source']);
    }

    // File does not exist.
    throw new \Exception(t('Resource is not a file: %source', array('%source' => $feed_config['source'])));
  }

  /**
   * {@inheritdoc}
   */
  public function feedForm(array $form, array &$form_state, FeedInterface $feed) {
    $feed_config = $feed->getConfigurationFor($this);
    $form['fetcher']['#tree'] = TRUE;
    $form['fetcher']['fid'] = array(
      '#type' => 'value',
      '#value' => empty($feed_config['fid']) ? 0 : $feed_config['fid'],
    );
    $form['fetcher']['source'] = array(
      '#type' => 'value',
      '#value' => empty($feed_config['source']) ? '' : $feed_config['source'],
    );
    $form['fetcher']['upload'] = array(
      '#type' => 'file',
      '#title' => t('File'),
      '#description' => empty($feed_config['source']) ? t('Select a file from your local system.') : t('Select a different file from your local system.'),
      '#theme' => 'feeds_upload',
      '#file_info' => empty($feed_config['fid']) ? NULL : file_load($feed_config['fid']),
      '#size' => 10,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function feedFormValidate(array $form, array &$form_state, FeedInterface $feed) {
    $values =& $form_state['values']['fetcher'];

    $feed_dir = $this->configuration['directory'];
    $validators = array(
      'file_validate_extensions' => array(
        $this->configuration['allowed_extensions'],
      ),
    );

    if (!file_prepare_directory($feed_dir, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
      if (user_access('administer feeds')) {
        $link = url('admin/structure/feeds/manage/' . $this->getPluginID() . '/settings/fetcher');
        form_set_error('feeds][upload][source', t('Upload failed. Please check the upload <a href="@link">settings.</a>', array('@link' => $link)));
      }
      else {
        form_set_error('feeds][upload][source', t('Upload failed. Please contact your site administrator.'));
      }
      watchdog('feeds', 'The upload directory %directory required by a feed could not be created or is not accessible. A newly uploaded file could not be saved in this directory as a consequence, and the upload was canceled.', array('%directory' => $feed_dir));
    }
    // Validate and save uploaded file.
    elseif ($file = file_save_upload('fetcher', $validators, $feed_dir, 0)) {
      $values['source'] = $file->getFileUri();
      $values['file'] = $file;
    }
    elseif (!$values['fid']) {
      form_set_error('files][fetcher', t('Please upload a file.'));
    }
    else {
      // File present from previous upload. Nothing to validate.
    }
  }

  /**
   * {@inheritdoc}
   */
  public function sourceSave(FeedInterface $feed) {
    $feed_config = $feed->getConfigurationFor($this);

    // If a new file is present, delete the old one and replace it with the new
    // one.
    if (isset($feed_config['file'])) {
      $file = $feed_config['file'];
      if (isset($feed_config['fid'])) {
        $this->deleteFile($feed_config['fid'], $feed->id());
      }
      $file->setPermanent();
      file_usage()->add($file, 'feeds', $this->getPluginID(), $feed->id());

      $feed_config['fid'] = $file->id();
      unset($feed_config['file']);
      $feed->setConfigFor($this, $feed_config);
      $file->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function sourceDelete(FeedInterface $feed) {
    $feed_config = $feed->getConfigurationFor($this);
    if (isset($feed_config['fid'])) {
      $this->deleteFile($feed_config['fid'], $feed->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigurationDefaults() {
    $schemes = $this->getSchemes();
    $scheme = in_array('private', $schemes) ? 'private' : 'public';

    return array(
      'allowed_extensions' => 'txt csv tsv xml opml',
      'directory' => $scheme . '://feeds',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form['allowed_extensions'] = array(
      '#type' => 'textfield',
      '#title' => t('Allowed file extensions'),
      '#description' => t('Allowed file extensions for upload.'),
      '#default_value' => $this->configuration['allowed_extensions'],
    );
    $form['directory'] = array(
      '#type' => 'textfield',
      '#title' => t('Upload directory'),
      '#description' => t('Directory where uploaded files get stored. Prefix the path with a scheme. Available schemes: @schemes.', array('@schemes' => implode(', ', $this->getSchemes()))),
      '#default_value' => $this->configuration['directory'],
      '#required' => TRUE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {

    $values =& $form_state['values']['fetcher']['config'];

    $values['directory'] = trim($values['directory']);

    // Ensure that the upload directory field is not empty.
    if (!$values['directory']) {
      form_set_error('directory', t('Please specify an upload directory.'));
      // Do not continue validating the directory if none was specified.
      return;
    }

    // Validate the URI scheme of the upload directory.
    $scheme = file_uri_scheme($values['directory']);
    if (!$scheme || !in_array($scheme, $this->getSchemes())) {
      form_set_error('directory', t('Please enter a valid scheme into the directory location.'));

      // Return here so that attempts to create the directory below don't
      // throw warnings.
      return;
    }

    // Ensure that the upload directory exists.
    if (!file_prepare_directory($values['directory'], FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
      form_set_error('directory', t('The chosen directory does not exist and attempts to create it failed.'));
    }
  }

  /**
   * Deletes a file.
   *
   * @param int $fid
   *   The file id.
   * @param int $fid
   *   The feed node's id, or 0 if a standalone feed.
   *
   * @return bool|array
   *   TRUE for success, FALSE in the event of an error, or an array if the file
   *   is being used by any modules.
   *
   * @see file_delete()
   */
  protected function deleteFile($fid, $feed_id) {
    if ($file = file_load($fid)) {
      file_usage()->delete($file, 'feeds', $this->getPluginID(), $feed_id);
    }
    return FALSE;
  }

  /**
   * Returns available schemes.
   *
   * @return array
   *   The available schemes.
   */
  protected function getSchemes() {
    return array_keys(file_get_stream_wrappers(STREAM_WRAPPERS_WRITE_VISIBLE));
  }

}
