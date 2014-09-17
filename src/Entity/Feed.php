<?php

/**
 * @file
 * Contains \Drupal\feeds\Entity\Feed.
 */

namespace Drupal\feeds\Entity;

use Drupal\Component\Utility\String;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\feeds\Exception\InterfaceNotImplementedException;
use Drupal\feeds\Exception\LockException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\ClearableInterface;
use Drupal\feeds\Plugin\Type\FeedsPluginInterface;
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
 *     "view_builder" = "Drupal\feeds\FeedViewBuilder",
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
 *     "label" = "title",
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
 *     "admin-form" = "feeds.importer_edit"
 *   }
 * )
 */
class Feed extends ContentEntityBase implements FeedInterface {

  /**
   * The cached result from getImporter() since it gets called many times.
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
    return $this->get('title')->value;
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
      // If fetcher result is empty, we are starting a new import, log.
      if (empty($this->get('fetcher_result')->value)) {
        $this->setState(StateInterface::START, time());
      }

      $importer = $this->getImporter();

      // Fetch.
      if (empty($this->get('fetcher_result')->value) || $this->progressParsing() == StateInterface::BATCH_COMPLETE) {
        $fetcher_result = $importer->getFetcher()->fetch($this);

        // If the fetcher did not return a result, then there's nothing to do.
        if (!$fetcher_result) {
          $this->cleanUp();
          return StateInterface::BATCH_COMPLETE;
        }

        $this->set('fetcher_result', $fetcher_result);
        // Clean the parser's state, we are parsing an entirely new file.
        $this->setState(StateInterface::PARSE, NULL);
      }

      // Parse.
      $parser_result = $importer->getParser()->parse($this, $this->get('fetcher_result')->value);
      module_invoke_all('feeds_after_parse', $this, $parser_result);

      // Process.
      // @todo Create a ProcessorWrapper plugin?
      $processor_state = $this->getState(StateInterface::PROCESS);
      $processor = $importer->getProcessor();
      $processor->process($this, $processor_state, $parser_result);
    }
    catch (Exception $e) {
      // Do nothing. Will thow later.
    }

    // Clean up.
    $result = $this->progressImporting();

    if ($result == StateInterface::BATCH_COMPLETE || isset($e)) {
      $this->cleanUp();
    }

    $this->save();

    if (isset($e)) {
      throw $e;
    }

    return $result;
  }

  /**
   * Cleans up after an import.
   */
  protected function cleanUp() {
    $processor_state = $this->getState(StateInterface::PROCESS);
    $this->getImporter()->getProcessor()->setMessages($this, $processor_state);
    $this->set('imported', time());
    $this->log('import', 'Imported in !s s', array('!s' => $this->get('imported')->value - $this->getState(StateInterface::START), WATCHDOG_INFO));
    module_invoke_all('feeds_after_import', $this);

    // Unset.
    $this->get('fetcher_result')->setValue(NULL);
    $this->get('state')->setValue(NULL);
    $this->releaseLock();
  }

  /**
   * {@inheritdoc}
   *
   * @todo Should we acquire a lock here? If not, we shouldn't lose $raw.
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
      module_invoke_all('feeds_after_import', $this);
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
   *
   * @todo Convert to proper field item.
   */
  public function getState($stage) {
    $state = $this->get('state')->$stage;
    if (!$state) {
      $state = new State();
      $this->get('state')->$stage = $state;
    }
    return $state;
  }

  public function setState($stage, $state) {
    $this->get('state')->$state = $state;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function progressParsing() {
    return $this->getState(StateInterface::PARSE)->progress;
  }

  /**
   * {@inheritdoc}
   */
  public function progressImporting() {
    $fetcher = $this->getState(StateInterface::FETCH);
    $parser = $this->getState(StateInterface::PARSE);

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
    return $this->getState(StateInterface::CLEAR)->progress;
  }

  /**
   * {@inheritdoc}
   */
  public function progressExpiring() {
    return $this->getState(StateInterface::EXPIRE)->progress;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemCount() {
    return $this->getImporter()->getProcessor()->getItemCount($this);
  }

  /**
   * {@inheritdoc}
   *
   * @todo Perform some validation.
   */
  public function existing() {
    return $this;
  }

  public function getConfiguration() {
    $configuration = $this->get('config')->getValue();
    return reset($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigurationFor(FeedsPluginInterface $client) {
    $type = $client->pluginType();

    $configuration = $this->get('config')->$type;

    return array_intersect_key($configuration, $client->sourceDefaults()) + $client->sourceDefaults();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfigurationFor(FeedsPluginInterface $client, array $configuration) {
    $type = $client->pluginType();
    $this->get('config')->$type = $configuration;

    return $this;
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
  public function getSource() {
    $configuration = $this->get('config')->fetcher;
    if (isset($configuration['source'])) {
      return $configuration['source'];
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
    // Before saving the feed, set changed time.
    $this->set('changed', REQUEST_TIME);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    // Alert implementers of FeedInterface to the fact that we're saving.
    foreach ($this->getImporter()->getPlugins() as $plugin) {
      $plugin->onFeedSave($this, $update);
    }

    // Store the source property of the fetcher in a separate column so that we
    // can do fast lookups on it.
    $this->set('source', $this->getSource());

    $storage_controller->updateFeedConfig($this);
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

    // Group feeds by imporer.
    $grouped = array();
    foreach ($feeds as $feed) {
      $grouped[$feed->bundle()][] = $feed;
    }

    // Alert plugins that we are deleting.
    foreach ($grouped as $group) {
      // Grab the first feed to get its importer.
      $feed = reset($group);
      foreach ($feed->getImporter()->getPlugins() as $plugin) {
        $plugin->onFeedDeleteMultiple($group);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function unlock() {
    \Drupal::entityManager()->getStorageController($this->entityType)->unlockFeed($this);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions($entity_type) {
    $fields = array();

    $fields['fid'] = FieldDefinition::create('integer')
      ->setLabel(t('Feed ID'))
      ->setDescription(t('The feed ID.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = FieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The feed UUID.'))
      ->setReadOnly(TRUE);

    $fields['importer'] = FieldDefinition::create('string')
      ->setLabel(t('Importer'))
      ->setDescription(t('The feeds importer.'))
      ->setReadOnly(TRUE);

    $fields['title'] = FieldDefinition::create('text')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of this feed, always treated as non-markup plain text.'))
      ->setRequired(TRUE)
      // ->setTranslatable(TRUE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ));

    $fields['uid'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('User ID'))
      ->setDescription(t('The user ID of the feed author.'))
      ->setSettings(array(
        'target_type' => 'user',
        'default_value' => 0,
      ));

    $fields['status'] = FieldDefinition::create('boolean')
      ->setLabel(t('Importing status'))
      ->setDescription(t('A boolean indicating whether the feed is active.'));

    // @todo Convert to a "created" field in https://drupal.org/feed/2145103.
    $fields['created'] = FieldDefinition::create('integer')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the feed was created.'));

    // @todo Convert to a "changed" field in https://drupal.org/feed/2145103.
    $fields['changed'] = FieldDefinition::create('integer')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the feed was last edited.'))
      ->setPropertyConstraints('value', array('EntityChanged' => array()));

    $fields['imported'] = FieldDefinition::create('integer')
      ->setLabel(t('Imported'))
      ->setDescription(t('The time that the feed was imported.'));

    $fields['source'] = FieldDefinition::create('uri')
      ->setLabel(t('Source'))
      ->setDescription(t('The source of the feed.'))
      ->setSettings(array('default_value' => ''));

    $fields['config'] = FieldDefinition::create('map')
      ->setLabel(t('Config'))
      ->setDescription(t('The config of the feed.'));

    $fields['fetcher_result'] = FieldDefinition::create('feeds_serialized')
      ->setLabel(t('Fetcher result'))
      ->setDescription(t('The source of the feed.'));

    $fields['state'] = FieldDefinition::create('map')
      ->setLabel(t('State'))
      ->setDescription(t('The source of the feed.'))
      ->setSettings(array('default_value' => array()));

    return $fields;
  }

}
