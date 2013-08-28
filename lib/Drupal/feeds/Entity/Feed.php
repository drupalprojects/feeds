<?php

/**
 * @file
 * Contains \Drupal\feeds\Entity\Feed.
 */

namespace Drupal\feeds\Entity;

use Drupal\Component\Utility\String;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Entity\EntityNG;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\feeds\Exception\InterfaceNotImplementedException;
use Drupal\feeds\Exception\LockException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\FeedsPluginInterface;
use Drupal\feeds\Plugin\ClearableInterface;
use Drupal\feeds\PuSH\PuSHFetcherInterface;
use Drupal\feeds\State;
use Drupal\feeds\StateInterface;
use Drupal\job_scheduler\JobScheduler;

/**
 * Defines the feed entity class.
 *
 * @EntityType(
 *   id = "feeds_feed",
 *   label = @Translation("Feed"),
 *   bundle_label = @Translation("Importer"),
 *   module = "feeds",
 *   controllers = {
 *     "storage" = "Drupal\feeds\FeedStorageController",
 *     "render" = "Drupal\feeds\FeedRenderController",
 *     "access" = "Drupal\feeds\FeedAccessController",
 *     "form" = {
 *       "create" = "Drupal\feeds\FeedFormController",
 *       "update" = "Drupal\feeds\FeedFormController",
 *       "delete" = "Drupal\feeds\Form\FeedDeleteForm",
 *       "import" = "Drupal\feeds\Form\FeedImportForm",
 *       "clear" = "Drupal\feeds\Form\FeedClearForm",
 *       "unlock" = "Drupal\feeds\Form\FeedUnlockForm",
 *       "default" = "Drupal\feeds\FeedFormController"
 *     },
 *     "list" = "Drupal\Core\Entity\EntityListController"
 *   },
 *   base_table = "feeds_feed",
 *   uri_callback = "feeds_feed_uri",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "fid",
 *     "bundle" = "importer",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   },
 *   bundle_keys = {
 *     "bundle" = "importer"
 *   },
 *   route_base_path = "admin/structure/feeds/manage/{bundle}",
 *   menu_base_path = "feed/%feed",
 *   permission_granularity = "bundle",
 *   links = {
 *     "canonical" = "/feed/{feeds_feed}",
 *     "edit-form" = "/feed/{feeds_feed}/edit",
 *   }
 * )
 */
class Feed extends EntityNG implements FeedInterface {

  /**
   * The cached result from getImporter() since that gets called many times.
   *
   * @var \Drupal\feeds\ImporterInterface
   */
  protected $cachedImporter;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->get('fid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function label($langcode = NULL) {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getimportedTime() {
    return $this->get('imported')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getImporter() {

    if ($this->cachedImporter) {
      return $this->cachedImporter;
    }

    if ($importer = entity_load('feeds_importer', $this->bundle())) {
      $this->cachedImporter = $importer;
      return $importer;
    }

    throw new \RuntimeException(String::format('The importer, @importer, for this feed does not exist.', array('@importer' => $this->bundle())));
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    $result = $this->getImporter()->getFetcher()->fetch($this);
    $result = $this->getImporter()->getParser()->parse($this, $result);
    module_invoke_all('feeds_after_parse', $this, $result);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function startImport() {
    module_invoke_all('feeds_before_import', $this);
    $this->getImporter()->getPlugin('manager')->startImport($this);
  }

  /**
   * {@inheritdoc}
   */
  public function startClear() {
    module_invoke_all('feeds_before_clear', $this);
    $this->getImporter()->getPlugin('manager')->startClear($this);
  }

  /**
   * {@inheritdoc}
   */
  public function import() {
    $this->acquireLock();
    try {
      $state = $this->get('state')->value;
      // If fetcher result is empty, we are starting a new import, log.
      if (empty($this->get('fetcher_result')->value)) {
        $state[StateInterface::START] = time();
        $this->set('state', $state);
      }

      // Fetch.
      if (empty($this->get('fetcher_result')->value) || $this->progressParsing() == StateInterface::BATCH_COMPLETE) {
        $this->set('fetcher_result', $this->getImporter()->getFetcher()->fetch($this));
        // Clean the parser's state, we are parsing an entirely new file.
        $state = $this->get('state')->value;
        unset($state[StateInterface::PARSE]);
        $this->set('state', $state);
      }

      // Parse.
      $parser_result = $this->getImporter()->getParser()->parse($this, $this->get('fetcher_result')->value);
      module_invoke_all('feeds_after_parse', $this, $parser_result);

      // // Process.
      $this->getImporter()->getProcessor()->process($this, $parser_result);
    }
    catch (Exception $e) {
      // Do nothing.
    }
    $this->releaseLock();

    // Clean up.
    $result = $this->progressImporting();

    if ($result == StateInterface::BATCH_COMPLETE || isset($e)) {
      $state = $this->get('state')->value;
      $this->set('imported', time());
      $this->log('import', 'Imported in !s s', array('!s' => $this->get('imported')->value - $state[StateInterface::START]), WATCHDOG_INFO);
      module_invoke_all('feeds_after_import', $this);
      $this->get('fetcher_result')->setValue(NULL);
      $this->get('state')->setValue(NULL);
    }

    $this->save();

    if (isset($e)) {
      throw $e;
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function importRaw($raw) {
    // Fetch.
    $importer = $this->getImporter();
    $fetcher = $importer->getFetcher();
    if ($fetcher instanceof PuSHFetcherInterface) {
      $fetcher_result = $fetcher->push($this, $raw);

      // Parse.
      $parser_result = $importer->getParser()->parse($this, $fetcher_result);
      module_invoke_all('feeds_after_parse', $this, $parser_result);

      // // Process.
      $importer->getProcessor()->process($this, $parser_result);
    }
    else {
      throw new InterfaceNotImplementedException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clear() {
    $this->acquireLock();
    try {
      foreach ($this->getImporter()->getPlugins() as $plugin) {
        if ($plugin instanceof ClearableInterface) {
          $plugin->clear($this);
        }
      }
    }
    catch (Exception $e) {
      // Do nothing yet.
    }
    $this->releaseLock();

    // Clean up.
    $result = $this->progressClearing();

    if ($result == StateInterface::BATCH_COMPLETE || isset($e)) {
      module_invoke_all('feeds_after_clear', $this);
      $this->get('state')->setValue(NULL);
    }

    $this->save();

    if (isset($e)) {
      throw $e;
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function expire() {
    $this->acquireLock();
    try {
      $result = $this->getImporter()->getProcessor()->expire($this);
    }
    catch (Exception $e) {
      // Will throw after the lock is released.
    }
    $this->releaseLock();

    if (isset($e)) {
      throw $e;
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function schedule() {
    $this->scheduleImport();
    $this->scheduleExpire();
  }

  /**
   * {@inheritdoc}
   */
  public function scheduleImport() {
    $this->getImporter()->getPlugin('scheduler')->scheduleImport($this);
  }

  /**
   * {@inheritdoc}
   */
  public function scheduleExpire() {
    $this->getImporter()->getPlugin('scheduler')->scheduleExpire($this);
  }

  /**
   * {@inheritdoc}
   */
  public function state($stage) {
    $state = $this->get('state')->value;
    if (!isset($state[$stage])) {
      $state[$stage] = new State();
    }
    $this->set('state', $state);
    return $state[$stage];
  }

  /**
   * {@inheritdoc}
   */
  public function progressParsing() {
    return $this->state(StateInterface::PARSE)->progress;
  }

  /**
   * {@inheritdoc}
   */
  public function progressImporting() {
    $fetcher = $this->state(StateInterface::FETCH);
    $parser = $this->state(StateInterface::PARSE);
    if ($fetcher->progress == StateInterface::BATCH_COMPLETE && $parser->progress == StateInterface::BATCH_COMPLETE) {
      return StateInterface::BATCH_COMPLETE;
    }
    // Fetching envelops parsing.
    // @todo: this assumes all fetchers neatly use total. May not be the case.
    $fetcher_fraction = $fetcher->total ? 1.0 / $fetcher->total : 1.0;
    $parser_progress = $parser->progress * $fetcher_fraction;
    $result = $fetcher->progress - $fetcher_fraction + $parser_progress;

    if ($result >= StateInterface::BATCH_COMPLETE) {
      return 0.99;
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function progressClearing() {
    return $this->state(StateInterface::CLEAR)->progress;
  }

  /**
   * {@inheritdoc}
   */
  public function progressExpiring() {
    return $this->state(StateInterface::EXPIRE)->progress;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemCount() {
    return $this->getImporter()->getProcessor()->getItemCount($this);
  }

  /**
   * {@inheritdoc}
   */
  public function existing() {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigurationFor(FeedsPluginInterface $client) {
    $type = $client->pluginType();

    if (isset($this->get('config')->value[$type])) {
      return $this->get('config')->value[$type];
    }

    return $client->sourceDefaults();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfigurationFor(FeedsPluginInterface $client, array $config) {
    $this_config = $this->get('config')->value;
    $this_config[$client->pluginType()] = $config;
    $this->set('config', $this_config);

    return $this;
  }

  /**
   * Returns the default configuration for this feed.
   *
   * @return array
   *   The defualt configuration for the feed plus plugins.
   */
  protected function getDefaultConfiguration() {
    // Collect information from plugins.
    $defaults = array();
    foreach ($this->getImporter()->getPlugins() as $plugin) {
      $defaults[$plugin->pluginType()] = $plugin->sourceDefaults();
    }

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function log($type, $message, $variables = array(), $severity = WATCHDOG_NOTICE) {
    if ($severity < WATCHDOG_NOTICE) {
      $error = &drupal_static('feeds_log_error', FALSE);
      $error = TRUE;
    }
    db_insert('feeds_log')
      ->fields(array(
        'fid' => $this->id(),
        'log_time' => time(),
        'request_time' => REQUEST_TIME,
        'type' => $type,
        'message' => $message,
        'variables' => serialize($variables),
        'severity' => $severity,
      ))
      ->execute();

      return $this;
  }

  /**
   * Acquires a lock for this feed.
   *
   * @throws \Drupal\feeds\Exception\LockException
   *   If a lock for the requested job could not be acquired.
   *
   * @return self
   *   Returns the Feed for method chaining.
   */
  protected function acquireLock() {
    if (!\Drupal::lock()->acquire("feeds_feed_{$this->id()}", 60.0)) {
      throw new LockException(String::format('Cannot acquire lock for feed @id / @fid.', array('@id' => $this->getImporter()->id(), '@fid' => $this->id())));
    }

    return $this;
  }

  /**
   * Releases a lock for this source.
   *
   * @return self
   *   Returns the Feed for method chaining.
   */
  protected function releaseLock() {
    \Drupal::lock()->release("feeds_feed_{$this->id()}");

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->get('config')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSource() {
    $configuration = $this->getConfiguration();
    if (isset($configuration['fetcher']) && isset($configuration['fetcher']['source'])) {
      return $configuration['fetcher']['source'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthor() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setAuthorId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    return (bool) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setActive($active) {
    $this->set('status', $active ? self::ACTIVE : self::INACTIVE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    // $feed->state = isset($feed->state) ? $feed->state : FALSE;
    // $feed->fetcher_result = isset($feed->fetcher_result) ? $feed->fetcher_result : FALSE;
    // Before saving the feeds, set changed and revision times.
    $this->set('changed', REQUEST_TIME);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    // Alert implementers of FeedInterface to the fact that we're saving.
    foreach ($this->getImporter()->getPlugins() as $plugin) {
      $plugin->sourceSave($this);
    }
    $config = $this->getConfiguration();

    // Store the source property of the fetcher in a separate column so that we
    // can do fast lookups on it.
    $source = '';
    if (isset($config['fetcher']['source'])) {
      $source = $config['fetcher']['source'];
    }
    $this->set('source', $source);

    // @todo move this to the storage controller.
    db_update('feeds_feed')
      ->condition('fid', $this->id())
      ->fields(array(
        'source' => $source,
        'config' => serialize($config),
      ))
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageControllerInterface $storage_controller, array $feeds) {
    // Delete values from other tables also referencing these feeds.
    $ids = array_keys($feeds);

    // @todo Create a log controller or some sort of log handler that D8 uses.
    db_delete('feeds_log')
      ->condition('fid', $ids)
      ->execute();

    // Alert plugins that we are deleting.
    foreach ($feeds as $feed) {
      foreach ($feed->getImporter()->getPlugins() as $plugin) {
        $plugin->sourceDelete($feed);
      }

      // Remove from schedule.
      $job = array(
        'type' => $feed->bundle(),
        'id' => $feed->id(),
      );
      JobScheduler::get('feeds_feed_import')->remove($job);
      JobScheduler::get('feeds_feed_expire')->remove($job);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function unlock() {
    \Drupal::entityManager()->getStorageController($this->entityType)->unlock($this);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions($entity_type) {
    $properties['fid'] = array(
      'label' => t('Feed ID'),
      'description' => t('The feed ID.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $properties['uuid'] = array(
      'label' => t('UUID'),
      'description' => t('The feed UUID.'),
      'type' => 'string_field',
      'read-only' => TRUE,
    );
    $properties['importer'] = array(
      'label' => t('Importer'),
      'description' => t('The feeds importer.'),
      'type' => 'string_field',
      'read-only' => TRUE,
    );
    $properties['name'] = array(
      'label' => t('Name'),
      'description' => t('The name of this feed, always treated as non-markup plain text.'),
      'type' => 'string_field',
    );
    $properties['uid'] = array(
      'label' => t('User ID'),
      'description' => t('The user ID of the feed author.'),
      'type' => 'entity_reference_field',
      'settings' => array(
        'target_type' => 'user',
        'default_value' => 0,
      ),
    );
    $properties['status'] = array(
      'label' => t('Import status'),
      'description' => t('A boolean indicating whether the feed is active.'),
      'type' => 'boolean_field',
    );
    $properties['created'] = array(
      'label' => t('Created'),
      'description' => t('The time that the feed was created.'),
      'type' => 'integer_field',
    );
    $properties['changed'] = array(
      'label' => t('Changed'),
      'description' => t('The time that the feed was last edited.'),
      'type' => 'integer_field',
    );
    $properties['imported'] = array(
      'label' => t('Imported'),
      'description' => t('The time that the feed was last imported.'),
      'type' => 'integer_field',
    );
    $properties['source'] = array(
      'label' => t('Source'),
      'description' => t('The source of the feed.'),
      'type' => 'uri_field',
    );
    $properties['config'] = array(
      'label' => t('Config'),
      'description' => t('The config of the feed.'),
      'type' => 'feeds_serialized_field',
      'settings' => array('default_value' => array()),
    );
    $properties['fetcher_result'] = array(
      'label' => t('Fetcher result'),
      'description' => t('The source of the feed.'),
      'type' => 'feeds_serialized_field',
      'settings' => array('default_value' => array()),
    );
    $properties['state'] = array(
      'label' => t('State'),
      'description' => t('The source of the feed.'),
      'type' => 'feeds_serialized_field',
      'settings' => array('default_value' => array()),
    );

    return $properties;
  }

}
