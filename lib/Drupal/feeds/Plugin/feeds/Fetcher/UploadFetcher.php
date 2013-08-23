<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Fetcher\UploadFetcher.
 */

namespace Drupal\feeds\Plugin\feeds\Fetcher;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedPluginFormInterface;
use Drupal\feeds\Result\FetcherResult;
use Drupal\feeds\Plugin\ConfigurablePluginBase;
use Drupal\feeds\Plugin\FetcherInterace;

/**
 * Defines a file upload fetcher.
 *
 * @Plugin(
 *   id = "upload",
 *   title = @Translation("Upload fetcher"),
 *   description = @Translation("Upload content from a local file.")
 * )
 */
class UploadFetcher extends ConfigurablePluginBase implements FeedPluginFormInterface, ContainerFactoryPluginInterface, FetcherInterace {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The file usage backend.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * The file storage backend.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $fileStorage;

  /**
   * Constructs an UploadFetcher object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param AccountInterface $account
   *   The current user.
   * @param FileUsageInterface $file_usage
   *   The file usage backend.
   * @param EntityStorageControllerInterface $file_storage
   *   The file storage controller.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, AccountInterface $account, FileUsageInterface $file_usage, EntityStorageControllerInterface $file_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->account = $account;
    $this->fileUsage = $file_usage;
    $this->fileStorage = $file_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    $user = $container->get('request')->attributes->get('_account');
    return new static($configuration, $plugin_id, $plugin_definition, $user, $container->get('file.usage'));
  }

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
      '#file_info' => empty($feed_config['fid']) ? NULL : $this->fileStorage->load($feed_config['fid']),
      '#size' => 10,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function feedFormValidate(array $form, array &$form_state, FeedInterface $feed) {
    // @todo
    $values =& $form_state['values']['fetcher'];

    $feed_dir = $this->configuration['directory'];
    $validators = array(
      'file_validate_extensions' => array(
        $this->configuration['allowed_extensions'],
      ),
    );

    if (!file_prepare_directory($feed_dir, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
      if ($this->account->hasPermission('administer feeds')) {
        $link = url('admin/structure/feeds/manage/' . $this->getPluginId() . '/settings/fetcher');
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
      $this->fileUsage->add($file, 'feeds', $this->getPluginId(), $feed->id());

      $feed_config['fid'] = $file->id();
      unset($feed_config['file']);
      $feed->setConfigurationFor($this, $feed_config);
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
  protected function getDefaultConfiguration() {
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
  public function buildConfigurationForm(array $form, array &$form_state) {
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
  public function validateConfigurationForm(array &$form, array &$form_state) {

    $values =& $form_state['values']['fetcher']['configuration'];

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
   * @param int $file_id
   *   The file id.
   * @param int $feed_id
   *   The feed id.
   *
   * @return bool|array
   *   TRUE for success, FALSE in the event of an error, or an array if the file
   *   is being used by any modules.
   *
   * @see file_delete()
   */
  protected function deleteFile($file_id, $feed_id) {
    if ($file = $this->fileStorage->load($file_id)) {
      $this->fileUsage->delete($file, 'feeds', $this->getPluginId(), $feed_id);
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
