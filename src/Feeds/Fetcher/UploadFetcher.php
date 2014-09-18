<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Fetcher\UploadFetcher.
 */

namespace Drupal\feeds\Feeds\Fetcher;

use Drupal\Component\Utility\String;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\ConfigurablePluginBase;
use Drupal\feeds\Plugin\Type\FeedPluginFormInterface;
use Drupal\feeds\Plugin\Type\Fetcher\FetcherInterface;
use Drupal\feeds\Result\FetcherResult;
use Drupal\file\FileUsage\FileUsageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a file upload fetcher.
 *
 * @Plugin(
 *   id = "upload",
 *   title = @Translation("Upload"),
 *   description = @Translation("Upload content from a local file.")
 * )
 */
class UploadFetcher extends ConfigurablePluginBase implements FeedPluginFormInterface, ContainerFactoryPluginInterface, FetcherInterface {

  /**
   * The file usage backend.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * The file storage backend.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fileStorage;

  /**
   * The feed configuration.
   *
   * Used only during form processing.
   *
   * @var array
   */
  protected $feedConfig;

  /**
   * Constructs an UploadFetcher object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param \Drupal\file\FileUsage\FileUsageInterface $file_usage
   *   The file usage backend.
   * @param \Drupal\Core\Entity\EntityStorageInterface $file_storage
   *   The file storage controller.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, FileUsageInterface $file_usage, EntityStorageInterface $file_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fileUsage = $file_usage;
    $this->fileStorage = $file_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // $account = $container->get('current_user');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('file.usage'),
      $container->get('entity.manager')->getStorage('file')
    );
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
    throw new \Exception(String::format('Resource is not a file: %source', array('%source' => $feed_config['source'])));
  }

  /**
   * {@inheritdoc}
   */
  public function sourceDefaults() {
    return array('fid' => 0, 'source' => '');
  }

  /**
   * {@inheritdoc}
   */
  public function buildFeedForm(array $form, FormStateInterface $form_state, FeedInterface $feed) {
    $this->feedConfig = $feed->getConfigurationFor($this);

    $form['fetcher']['#tree'] = TRUE;
    $form['fetcher']['upload'] = array(
      '#type' => 'managed_file',
      '#title' => $this->t('File'),
      '#description' => $this->t('Select a file from your local system.'),
      '#default_value' => array($this->feedConfig['fid']),
      '#upload_validators' => array(
        'file_validate_extensions' => array(
          $this->configuration['allowed_extensions'],
        ),
      ),
      '#upload_location' => $this->configuration['directory'],
      '#required' => TRUE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitFeedForm(array &$form, FormStateInterface $form_state, FeedInterface $feed) {
    // We need to store this for later so that we have the feed id.
    $this->feedConfig['new_fid'] = reset($form_state['values']['fetcher']['upload']);
  }

  /**
   * {@inheritdoc}
   */
  public function onFeedSave(FeedInterface $feed, $update) {
    // We are only interested in continuing if we came from a form submit.
    if (!$this->feedConfig) {
      return;
    }

    // New file found.
    if ($this->feedConfig['new_fid'] != $this->feedConfig['fid']) {
      $this->deleteFile($this->feedConfig['fid'], $feed->id());

      if ($this->feedConfig['new_fid']) {
        $file = $this->fileStorage->load($this->feedConfig['new_fid']);

        $this->fileUsage->add($file, 'feeds', $this->pluginType(), $feed->id());

        $file->setPermanent();
        $file->save();

        $this->feedConfig['fid'] = $this->feedConfig['new_fid'];
        $this->feedConfig['source'] = $file->getFileUri();
        $feed->setConfigurationFor($this, $this->feedConfig);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onFeedDeleteMultiple(array $feeds) {
    foreach ($feeds as $feed) {
      $feed_config = $feed->getConfigurationFor($this);
      if ($feed_config['fid']) {
        $this->deleteFile($feed_config['fid'], $feed->id());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
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
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['allowed_extensions'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Allowed file extensions'),
      '#description' => $this->t('Allowed file extensions for upload.'),
      '#default_value' => $this->configuration['allowed_extensions'],
    );
    $form['directory'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Upload directory'),
      '#description' => $this->t('Directory where uploaded files get stored. Prefix the path with a scheme. Available schemes: @schemes.', array('@schemes' => implode(', ', $this->getSchemes()))),
      '#default_value' => $this->configuration['directory'],
      '#required' => TRUE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

    $values =& $form_state['values']['fetcher']['configuration'];

    $values['directory'] = trim($values['directory']);
    $values['allowed_extensions'] = trim($values['allowed_extensions']);

    // Validate the URI scheme of the upload directory.
    $scheme = file_uri_scheme($values['directory']);
    if (!$scheme || !in_array($scheme, $this->getSchemes())) {
      form_error($form['fetcher_configuration']['directory'], $this->t('Please enter a valid scheme into the directory location.'));
      // Return here so that attempts to create the directory below don't throw
      // warnings.
      return;
    }

    // Ensure that the upload directory exists.
    if (!file_prepare_directory($values['directory'], FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
      form_error($form['fetcher_configuration']['directory'], $this->t('The chosen directory does not exist and attempts to create it failed.'));
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
   * @see file_delete()
   */
  protected function deleteFile($file_id, $feed_id) {
    if ($file = $this->fileStorage->load($file_id)) {
      $this->fileUsage->delete($file, 'feeds', $this->pluginType(), $feed_id);
    }
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
