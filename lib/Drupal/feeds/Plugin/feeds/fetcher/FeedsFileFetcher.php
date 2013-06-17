<?php

/**
 * @file
 * Home of the FeedsFileFetcher and related classes.
 */

namespace Drupal\feeds\Plugin\feeds\fetcher;

use Drupal\feeds\FeedsFileFetcherResult;
use Drupal\feeds\Plugin\FetcherBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\feeds\Plugin\Core\Entity\Feed;
use Exception;


/**
 * Defines a file fetcher.
 *
 * Upload files or give a directory.
 *
 * @Plugin(
 *   id = "file",
 *   title = @Translation("File fetcher"),
 *   description = @Translation("Upload content from a local file.")
 * )
 */
class FeedsFileFetcher extends FetcherBase {

  /**
   * Implements FetcherBase::fetch().
   */
  public function fetch(Feed $feed) {
    $feed_config = $feed->getConfigFor($this);

    // Just return a file fetcher result if this is a file.
    if (is_file($feed_config['source'])) {
      return new FeedsFileFetcherResult($feed_config['source']);
    }

    // Batch if this is a directory.
    $state = $feed->state(FEEDS_FETCH);
    $files = array();
    if (!isset($state->files)) {
      $state->files = $this->listFiles($feed_config['source']);
      $state->total = count($state->files);
    }
    if (count($state->files)) {
      $file = array_shift($state->files);
      $state->progress($state->total, $state->total - count($state->files));
      return new FeedsFileFetcherResult($file);
    }

    throw new Exception(t('Resource is not a file or it is an empty directory: %source', array('%source' => $feed_config['source'])));
  }

  /**
   * Returns an array of files in a directory.
   *
   * @param string $dir
   *   A stream wreapper URI that is a directory.
   *
   * @return array
   *   An array of stream wrapper URIs pointing to files. The array is empty if
   *   no files could be found. Never contains directories.
   */
  protected function listFiles($dir) {
    $dir = file_stream_wrapper_uri_normalize($dir);
    $files = array();
    if ($items = @scandir($dir)) {
      foreach ($items as $item) {
        if (is_file("$dir/$item") && strpos($item, '.') !== 0) {
          $files[] = "$dir/$item";
        }
      }
    }
    return $files;
  }

  /**
   * Source form.
   */
  public function sourceForm($feed_config) {
    $form = array();
    $form['fid'] = array(
      '#type' => 'value',
      '#value' => empty($feed_config['fid']) ? 0 : $feed_config['fid'],
    );
    if (empty($this->config['direct'])) {
      $form['source'] = array(
        '#type' => 'value',
        '#value' => empty($feed_config['source']) ? '' : $feed_config['source'],
      );
      $form['upload'] = array(
        '#type' => 'file',
        '#title' => empty($this->config['direct']) ? t('File') : NULL,
        '#description' => empty($feed_config['source']) ? t('Select a file from your local system.') : t('Select a different file from your local system.'),
        '#theme' => 'feeds_upload',
        '#file_info' => empty($feed_config['fid']) ? NULL : file_load($feed_config['fid']),
        '#size' => 10,
      );
    }
    else {
      $form['source'] = array(
        '#type' => 'textfield',
        '#title' => t('File'),
        '#description' => t('Specify a path to a file or a directory. Prefix the path with a scheme. Available schemes: @schemes.', array('@schemes' => implode(', ', $this->config['allowed_schemes']))),
        '#default_value' => empty($feed_config['source']) ? '' : $feed_config['source'],
      );
    }
    return $form;
  }

  /**
   * Overrides parent::sourceFormValidate().
   */
  public function sourceFormValidate(&$values) {
    $values['source'] = trim($values['source']);

    if (empty($this->config['direct'])) {

      $feed_dir = $this->config['directory'];
      $validators = array(
        'file_validate_extensions' => array(
          $this->config['allowed_extensions'],
        ),
      );

      if (!file_prepare_directory($feed_dir, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
        if (user_access('administer feeds')) {
          $link = url('admin/structure/feeds/manage/' . $this->id . '/settings/' . $this->pluginType());
          form_set_error('feeds][FeedsFileFetcher][source', t('Upload failed. Please check the upload <a href="@link">settings.</a>', array('@link' => $link)));
        }
        else {
          form_set_error('feeds][FeedsFileFetcher][source', t('Upload failed. Please contact your site administrator.'));
        }
        watchdog('feeds', 'The upload directory %directory required by a feed could not be created or is not accessible. A newly uploaded file could not be saved in this directory as a consequence, and the upload was canceled.', array('%directory' => $feed_dir));
      }
      // Validate and save uploaded file.
      elseif ($file = file_save_upload('fetcher', $validators, $feed_dir, 0)) {
        $values['source'] = $file->getFileUri();
        $values['file'] = $file;
      }
      elseif (empty($values['source'])) {
        form_set_error('files][fetcher', t('Please upload a file.'));
      }
      else {
        // File present from previous upload. Nothing to validate.
      }
    }
    else {
      // Check if chosen url scheme is allowed.
      $scheme = file_uri_scheme($values['source']);
      if (!$scheme || !in_array($scheme, $this->config['allowed_schemes'])) {
        form_set_error('feeds][FeedsFileFetcher][source', t("The file needs to reside within the site's files directory, its path needs to start with scheme://. Available schemes: @schemes.", array('@schemes' => implode(', ', $this->config['allowed_schemes']))));
      }
      // Check wether the given path exists.
      elseif (!file_exists($values['source'])) {
        form_set_error('feeds][FeedsFileFetcher][source', t('The specified file or directory does not exist.'));
      }
    }
  }

  /**
   * Overrides parent::sourceSave().
   */
  public function sourceSave(Feed $feed) {
    $feed_config = $feed->getConfigFor($this);

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
    }
  }

  /**
   * Overrides parent::sourceDelete().
   */
  public function sourceDelete(Feed $feed) {
    $feed_config = $feed->getConfigFor($this);
    if (isset($feed_config['fid'])) {
      $this->deleteFile($feed_config['fid'], $feed->id());
    }
  }

  /**
   * Overrides parent::configDefaults().
   */
  public function configDefaults() {
    $schemes = $this->getSchemes();
    $scheme = in_array('private', $schemes) ? 'private' : 'public';

    return array(
      'allowed_extensions' => 'txt csv tsv xml opml',
      'direct' => FALSE,
      'directory' => $scheme . '://feeds',
      'allowed_schemes' => $schemes,
    );
  }

  /**
   * Overrides parent::configForm().
   */
  public function configForm(&$form_state) {
    $form = array();
    $form['allowed_extensions'] = array(
      '#type' => 'textfield',
      '#title' => t('Allowed file extensions'),
      '#description' => t('Allowed file extensions for upload.'),
      '#default_value' => $this->config['allowed_extensions'],
    );
    $form['direct'] = array(
      '#type' => 'checkbox',
      '#title' => t('Supply path to file or directory directly'),
      '#description' => t('For experts. Lets users specify a path to a file <em>or a directory of files</em> directly,
        instead of a file upload through the browser. This is useful when the files that need to be imported
        are already on the server.'),
      '#default_value' => $this->config['direct'],
    );
    $form['directory'] = array(
      '#type' => 'textfield',
      '#title' => t('Upload directory'),
      '#description' => t('Directory where uploaded files get stored. Prefix the path with a scheme. Available schemes: @schemes.', array('@schemes' => implode(', ', $this->getSchemes()))),
      '#default_value' => $this->config['directory'],
      '#states' => array(
        'visible' => array(':input[name="direct"]' => array('checked' => FALSE)),
        'required' => array(':input[name="direct"]' => array('checked' => FALSE)),
      ),
    );
    if ($options = $this->getSchemeOptions()) {
      $form['allowed_schemes'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Allowed schemes'),
        '#default_value' => $this->config['allowed_schemes'],
        '#options' => $options,
        '#description' => t('Select the schemes you want to allow for direct upload.'),
        '#states' => array(
          'visible' => array(':input[name="direct"]' => array('checked' => TRUE)),
        ),
      );
    }

    return $form;
  }

  /**
   * Overrides parent::configFormValidate().
   *
   * Ensure that the chosen directory is accessible.
   */
  public function configFormValidate(&$values) {

    $values['directory'] = trim($values['directory']);
    $values['allowed_schemes'] = array_filter($values['allowed_schemes']);

    if (!$values['direct']) {
      // Ensure that the upload directory field is not empty when not in
      // direct-mode.
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

  /**
   * Returns available scheme options for use in checkboxes or select list.
   *
   * @return array
   *   The available scheme array keyed scheme => description.
   */
  protected function getSchemeOptions() {
    $options = array();
    foreach (file_get_stream_wrappers(STREAM_WRAPPERS_WRITE_VISIBLE) as $scheme => $info) {
      $options[$scheme] = check_plain($scheme . ': ' . $info['description']);
    }
    return $options;
  }

}
