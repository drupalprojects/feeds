<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Fetcher\UploadFetcher.
 */

namespace Drupal\feeds\Feeds\Fetcher;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\ConfigurablePluginBase;
use Drupal\feeds\Plugin\Type\FeedPluginFormInterface;
use Drupal\feeds\Plugin\Type\Fetcher\FetcherInterface;
use Drupal\feeds\Result\FetcherResult;
use Drupal\feeds\StateInterface;
use Drupal\file\FileUsage\FileUsageInterface;

/**
 * Defines a file upload fetcher.
 *
 * @FeedsFetcher(
 *   id = "upload",
 *   title = @Translation("Upload"),
 *   description = @Translation("Upload content from a local file."),
 *   arguments = {
 *     "@file.usage",
 *     "@entity.manager",
 *     "@uuid",
 *     "@stream_wrapper_manager"
 *   }
 * )
 */
class UploadFetcher extends ConfigurablePluginBase implements FeedPluginFormInterface, FetcherInterface {

  /**
   * The file usage backend.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * The file storage backend.
   *
   * @var \Drupal\file\FileStorageInterface
   */
  protected $fileStorage;

  /**
   * The UUID generator.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuid;

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManager
   */
  protected $streamWrapperManager;

  /**
   * Constructs an UploadFetcher object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\file\FileUsage\FileUsageInterface $file_usage
   *   The file usage backend.
   * @param \Drupal\Core\Entity\EntityManagerInterface $file_storage
   *   The file storage controller.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   *   The UUID generator.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManager $stream_wrapper_manager
   *   The stream wrapper manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, FileUsageInterface $file_usage, EntityManagerInterface $entity_manager, UuidInterface $uuid, StreamWrapperManager $stream_wrapper_manager) {
    $this->fileUsage = $file_usage;
    $this->fileStorage = $entity_manager->getStorage('file');
    $this->uuid = $uuid;
    $this->streamWrapperManager = $stream_wrapper_manager;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function fetch(FeedInterface $feed, StateInterface $state) {
    if (is_file($feed->getSource())) {
      return new FetcherResult($feed->getSource());
    }

    // File does not exist.
    throw new \RuntimeException(SafeMarkup::format('Resource is not a file: %source', ['%source' => $feed->getSource()]));
  }

  /**
   * {@inheritdoc}
   */
  public function sourceDefaults() {
    return ['fid' => 0, 'usage_id' => ''];
  }

  /**
   * {@inheritdoc}
   */
  public function buildFeedForm(array $form, FormStateInterface $form_state, FeedInterface $feed) {
    $feed_config = $feed->getConfigurationFor($this);
    $form['source'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('File'),
      '#description' => $this->t('Select a file from your local system.'),
      '#default_value' => [$feed_config['fid']],
      '#upload_validators' => [
        'file_validate_extensions' => [
          $this->configuration['allowed_extensions'],
        ],
      ],
      '#upload_location' => $this->configuration['directory'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitFeedForm(array &$form, FormStateInterface $form_state, FeedInterface $feed) {
    // We need to store this for later so that we have the feed id.
    $new_fid = reset($form_state->getValue('source'));
    $feed_config = $feed->getConfigurationFor($this);

    // Generate a UUID that maps to this feed for file usage. We can't depend
    // on the feed id since this could be called before an id is assigned.
    $feed_config['usage_id'] = $feed_config['usage_id'] ?: $this->uuid->generate();

    if ($new_fid == $feed_config['fid']) {
      return;
    }

    $this->deleteFile($feed_config['fid'], $feed_config['usage_id']);

    if ($new_fid) {
      $file = $this->fileStorage->load($new_fid);
      $this->fileUsage->add($file, 'feeds', $this->pluginType(), $feed_config['usage_id']);
      $file->setPermanent();
      $file->save();

      $feed_config['fid'] = $new_fid;
      $feed->setSource($file->getFileUri());
    }

    $feed->setConfigurationFor($this, $feed_config);
  }

  /**
   * {@inheritdoc}
   */
  public function onFeedDeleteMultiple(array $feeds) {
    foreach ($feeds as $feed) {
      $feed_config = $feed->getConfigurationFor($this);
      if ($feed_config['fid']) {
        $this->deleteFile($feed_config['fid'], $feed_config['usage_id']);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $schemes = $this->getSchemes();
    $scheme = in_array('private', $schemes) ? 'private' : reset($schemes);

    return [
      'allowed_extensions' => 'txt csv tsv xml opml',
      'directory' => $scheme . '://feeds',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['allowed_extensions'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed file extensions'),
      '#description' => $this->t('Allowed file extensions for upload.'),
      '#default_value' => $this->configuration['allowed_extensions'],
    ];
    $form['directory'] = [
      '#type' => 'feeds_uri',
      '#title' => $this->t('Upload directory'),
      '#description' => $this->t('Directory where uploaded files get stored. Prefix the path with a scheme. Available schemes: @schemes.', ['@schemes' => implode(', ', $this->getSchemes())]),
      '#default_value' => $this->configuration['directory'],
      '#required' => TRUE,
      '#allowed_schemes' => $this->getSchemes(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values =& $form_state->getValues();

    $values['allowed_extensions'] = preg_replace('/\s+/', ' ', trim($values['allowed_extensions']));

    // Ensure that the upload directory exists.
    if (!empty($form['directory']) && !file_prepare_directory($values['directory'], FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
      $form_state->setError($form['directory'], $this->t('The chosen directory does not exist and attempts to create it failed.'));
    }
  }

  /**
   * Deletes a file.
   *
   * @param int $file_id
   *   The file id.
   * @param string $uuid
   *   The file UUID associated with this file.
   *
   * @see file_delete()
   */
  protected function deleteFile($file_id, $uuid) {
    if ($file = $this->fileStorage->load($file_id)) {
      $this->fileUsage->delete($file, 'feeds', $this->pluginType(), $uuid);
    }
  }

  /**
   * Returns available schemes.
   *
   * @return string[]
   *   The available schemes.
   */
  protected function getSchemes() {
    return array_keys($this->streamWrapperManager->getWrappers(StreamWrapperInterface::WRITE_VISIBLE));
  }

}
