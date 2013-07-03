<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\Core\Entity\Feed.
 */

namespace Drupal\feeds\Plugin\Core\Entity;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Entity\EntityNG;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedsState;
use Drupal\feeds\Plugin\PluginBase;
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
 *       "clear" = "Drupal\feeds\Form\FeedDeleteItemsForm",
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
   * The feed ID.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $fid;

  /**
   * The node UUID.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $uuid;

  /**
   * The feed importer (bundle).
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $importer;

  public $config = array();

  /**
   * The node title.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $title;

  /**
   * The feed source.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $source;

  /**
   * The node owner's user ID.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $uid;

  /**
   * The node published status indicator.
   *
   * Unpublished nodes are only visible to their authors and to administrators.
   * The value is either NODE_PUBLISHED or NODE_NOT_PUBLISHED.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $status;

  /**
   * The node creation timestamp.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $created;

  /**
   * The node modification timestamp.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $changed;

  /**
   * The node modification timestamp.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $imported;

  // A FeedState object holding the current import/clearing state of this
  // source.
  public $state;

  // Fetcher result, used to cache fetcher result when batching.
  public $fetcher_result;

  /**
   * Overrides Entity::__construct().
   */
  public function __construct(array $values, $entity_type, $bundle = FALSE) {
    parent::__construct($values, $entity_type, $bundle);
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityNG::init().
   */
  protected function init() {
    parent::init();
    // We unset all defined properties, so magic getters apply.
    unset($this->fid);
    unset($this->uuid);
    unset($this->importer);
    unset($this->title);
    unset($this->source);
    unset($this->uid);
    unset($this->status);
    unset($this->created);
    unset($this->changed);
    unset($this->imported);
    unset($this->config);
    unset($this->state);
    unset($this->fetcher_result);
  }

  /**
   * Implements Drupal\Core\Entity\EntityInterface::id().
   */
  public function id() {
    return $this->get('fid')->value;
  }

  /**
   * Implements Drupal\Core\Entity\EntityInterface::id().
   */
  public function label($langcode = NULL) {
    return $this->get('title')->value;
  }

  /**
   * Returns the FeedsImporter object that this source is expected to be used with.
   */
  public function getImporter() {
    return entity_load('feeds_importer', (string) $this->bundle());
  }

  /**
   * Preview = fetch and parse a feed.
   *
   * @return
   *   FeedsParserResult object.
   *
   * @throws
   *   Throws Exception if an error occurs when fetching or parsing.
   */
  public function preview() {
    $result = $this->getImporter()->fetcher->fetch($this);
    $result = $this->getImporter()->parser->parse($this, $result);
    module_invoke_all('feeds_after_parse', $this, $result);
    return $result;
  }

  /**
   * Start importing a source.
   *
   * This method starts an import job. Depending on the configuration of the
   * importer of this source, a Batch API job or a background job with Job
   * Scheduler will be created.
   *
   * @throws Exception
   *   If processing in background is enabled, the first batch chunk of the
   *   import will be executed on the current page request. This means that this
   *   method may throw the same exceptions as Feed::import().
   */
  public function startImport() {
    module_invoke_all('feeds_before_import', $this);
    $config = $this->getImporter()->getConfig();
    if ($config['process_in_background']) {
      $this->startBackgroundJob('import');
    }
    else {
      $this->startBatchAPIJob(t('Importing'), 'import');
    }
  }

  /**
   * Start deleting all imported items of a source.
   *
   * This method starts a clear job. Depending on the configuration of the
   * importer of this source, a Batch API job or a background job with Job
   * Scheduler will be created.
   *
   * @throws Exception
   *   If processing in background is enabled, the first batch chunk of the
   *   clear task will be executed on the current page request. This means that
   *   this method may throw the same exceptions as Feed::clear().
   */
  public function startClear() {
    $config = $this->getImporter()->getConfig();
    if ($config['process_in_background']) {
      $this->startBackgroundJob('clear');
    }
    else {
      $this->startBatchAPIJob(t('Deleting'), 'clear');
    }
  }

  /**
   * Schedule all periodic tasks for this source.
   */
  public function schedule() {
    $this->scheduleImport();
    $this->scheduleExpire();
  }

  /**
   * Schedule periodic or background import tasks.
   */
  public function scheduleImport() {
    // Check whether any fetcher is overriding the import period.
    $period = $this->getImporter()->config['import_period'];
    $fetcher_period = $this->getImporter()->fetcher->importPeriod($this);
    if (is_numeric($fetcher_period)) {
      $period = $fetcher_period;
    }
    $period = $this->progressImporting() === FEEDS_BATCH_COMPLETE ? $period : 0;
    $job = array(
      'type' => $this->getImporter()->id(),
      'id' => $this->id(),
      // Schedule as soon as possible if a batch is active.
      'period' => $period,
      'periodic' => TRUE,
    );
    if ($period == FEEDS_SCHEDULE_NEVER) {
      JobScheduler::get('feeds_feed_import')->remove($job);
    }
    else {
      JobScheduler::get('feeds_feed_import')->set($job);
    }
  }

  /**
   * Schedule background expire tasks.
   */
  public function scheduleExpire() {
    // Schedule as soon as possible if a batch is active.
    $period = $this->progressExpiring() === FEEDS_BATCH_COMPLETE ? 3600 : 0;

    $job = array(
      'type' => $this->getImporter()->id(),
      'id' => $this->id(),
      'period' => $period,
      'periodic' => TRUE,
    );
    if ($this->getImporter()->processor->expiryTime() == FEEDS_EXPIRE_NEVER) {
      JobScheduler::get('feeds_feed_expire')->remove($job);
    }
    else {
      JobScheduler::get('feeds_feed_expire')->set($job);
    }
  }

  /**
   * Schedule background clearing tasks.
   */
  public function scheduleClear() {
    $job = array(
      'type' => $this->getImporter()->id(),
      'id' => $this->id(),
      'period' => 0,
      'periodic' => TRUE,
    );
    // Remove job if batch is complete.
    if ($this->progressClearing() === FEEDS_BATCH_COMPLETE) {
      JobScheduler::get('feeds_feed_clear')->remove($job);
    }
    // Schedule as soon as possible if batch is not complete.
    else {
      JobScheduler::get('feeds_feed_clear')->set($job);
    }
  }

  /**
   * Import a source: execute fetching, parsing and processing stage.
   *
   * This method only executes the current batch chunk, then returns. If you are
   * looking to import an entire source, use Feed::startImport() instead.
   *
   * @return
   *   FEEDS_BATCH_COMPLETE if the import process finished. A decimal between
   *   0.0 and 0.9 periodic if import is still in progress.
   *
   * @throws
   *   Throws Exception if an error occurs when importing.
   */
  public function import() {
    $this->acquireLock();
    try {
      $state = $this->state->value;
      // If fetcher result is empty, we are starting a new import, log.
      if (empty($this->fetcher_result->value)) {
        $state[FEEDS_START] = time();
        $this->state = $state;
      }

      // Fetch.
      if (empty($this->fetcher_result->value) || FEEDS_BATCH_COMPLETE == $this->progressParsing()) {
        $this->fetcher_result = $this->getImporter()->fetcher->fetch($this);
        // Clean the parser's state, we are parsing an entirely new file.
        $state = $this->state->value;
        unset($state[FEEDS_PARSE]);
        $this->state = $state;
      }

      // Parse.
      $parser_result = $this->getImporter()->parser->parse($this, $this->fetcher_result->value);
      module_invoke_all('feeds_after_parse', $this, $parser_result);

      // // Process.
      $this->getImporter()->processor->process($this, $parser_result);
    }
    catch (Exception $e) {
      // Do nothing.
    }
    $this->releaseLock();

    // Clean up.
    $result = $this->progressImporting();

    if ($result == FEEDS_BATCH_COMPLETE || isset($e)) {
      $state = $this->state->value;
      $this->imported->value = time();
      $this->log('import', 'Imported in !s s', array('!s' => $this->imported->value - $state[FEEDS_START]), WATCHDOG_INFO);
      module_invoke_all('feeds_after_import', $this);
      unset($this->fetcher_result, $this->state);
    }

    $this->save();
    if (isset($e)) {
      throw $e;
    }
    return $result;
  }

  /**
   * Import a raw string.
   *
   * This does not batch. It assumes that the input is small enough to not need
   * it.
   *
   * @param string $raw
   *   (optional) A raw string to import. Defaults to null.
   *
   * @throws
   *   Throws Exception if an error occurs when importing.
   *
   * @todo Figure out batching.
   */
  public function importRaw($raw) {
    // Fetch.
    $fetcher_result = $this->getImporter()->fetcher->fetch($this, $raw);

    // Parse.
    $parser_result = $this->getImporter()->parser->parse($this, $fetcher_result);
    module_invoke_all('feeds_after_parse', $this, $parser_result);

    // // Process.
    $this->getImporter()->processor->process($this, $parser_result);
  }

  /**
   * Remove all items from a feed.
   *
   * This method only executes the current batch chunk, then returns. If you are
   * looking to delete all items of a source, use Feed::startClear()
   * instead.
   *
   * @return
   *   FEEDS_BATCH_COMPLETE if the clearing process finished. A decimal between
   *   0.0 and 0.9 periodic if clearing is still in progress.
   *
   * @throws
   *   Throws Exception if an error occurs when clearing.
   */
  public function clear() {
    $this->acquireLock();
    try {
      $this->getImporter()->fetcher->clear($this);
      $this->getImporter()->parser->clear($this);
      $this->getImporter()->processor->clear($this);
    }
    catch (Exception $e) {
      // Do nothing.
    }
    $this->releaseLock();

    // Clean up.
    $result = $this->progressClearing();

    if ($result == FEEDS_BATCH_COMPLETE || isset($e)) {
      module_invoke_all('feeds_after_clear', $this);
      unset($this->state);
    }

    $this->save();

    if (isset($e)) {
      throw $e;
    }

    return $result;
  }

  /**
   * Removes all expired items from a feed.
   */
  public function expire() {
    $this->acquireLock();
    try {
      $result = $this->getImporter()->processor->expire($this);
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
   * Report progress as float between 0 and 1. 1 = FEEDS_BATCH_COMPLETE.
   */
  public function progressParsing() {
    return $this->state(FEEDS_PARSE)->progress;
  }

  /**
   * Report progress as float between 0 and 1. 1 = FEEDS_BATCH_COMPLETE.
   */
  public function progressImporting() {
    $fetcher = $this->state(FEEDS_FETCH);
    $parser = $this->state(FEEDS_PARSE);
    if ($fetcher->progress == FEEDS_BATCH_COMPLETE && $parser->progress == FEEDS_BATCH_COMPLETE) {
      return FEEDS_BATCH_COMPLETE;
    }
    // Fetching envelops parsing.
    // @todo: this assumes all fetchers neatly use total. May not be the case.
    $fetcher_fraction = $fetcher->total ? 1.0 / $fetcher->total : 1.0;
    $parser_progress = $parser->progress * $fetcher_fraction;
    $result = $fetcher->progress - $fetcher_fraction + $parser_progress;
    if ($result == FEEDS_BATCH_COMPLETE) {
      return 0.99;
    }
    return $result;
  }

  /**
   * Report progress on clearing.
   */
  public function progressClearing() {
    return $this->state(FEEDS_PROCESS_CLEAR)->progress;
  }

  /**
   * Report progress on expiry.
   */
  public function progressExpiring() {
    return $this->state(FEEDS_PROCESS_EXPIRE)->progress;
  }

  /**
   * Return a state object for a given stage. Lazy instantiates new states.
   *
   * @todo Rename getConfigFor() accordingly to config().
   *
   * @param $stage
   *   One of FEEDS_FETCH, FEEDS_PARSE, FEEDS_PROCESS or FEEDS_PROCESS_CLEAR.
   *
   * @return
   *   The FeedsState object for the given stage.
   */
  public function state($stage) {
    $state = $this->state->value;
    if (!isset($state[$stage])) {
      $state[$stage] = new FeedsState();
    }
    $this->state = $state;
    return $state[$stage];
  }

  /**
   * Count items imported by this source.
   */
  public function itemCount() {
    return $this->getImporter()->processor->itemCount($this);
  }

  /**
   * Only return source if configuration is persistent and valid.
   */
  public function existing() {
    // If there is no feed nid given, there must be no content type specified.
    // If there is a feed nid given, there must be a content type specified.
    // Ensure that importer is persistent (= defined in code or DB).
    // Ensure that source is persistent (= defined in DB).

    return $this;
  }

  /**
   * Returns the configuration for a specific client class.
   *
   * @param FeedInterface $client
   *   An object that is an implementer of FeedInterface.
   *
   * @return
   *   An array stored for $client.
   */
  public function getConfigFor(PluginBase $client) {
    $id = $client->getPluginID();
    return isset($this->config->value[$id]) ? $this->config->value[$id] : $client->sourceDefaults();
  }

  /**
   * Sets the configuration for a specific client class.
   *
   * @param FeedInterface $client
   *   An object that is an implementer of FeedInterface.
   * @param $config
   *   The configuration for $client.
   *
   * @return
   *   An array stored for $client.
   */
  public function setConfigFor(PluginBase $client, $config) {
    $this_config = $this->config->value;
    $this_config[$client->getPluginID()] = $config;
    $this->config = $this_config;
  }

  /**
   * Return defaults for feed configuration.
   */
  public function configDefaults() {
    // Collect information from plugins.
    $defaults = array();
    foreach ($this->getImporter()->getPluginTypes() as $type) {
      $plugin = $this->getImporter()->$type;
      $defaults[$plugin->getPluginID()] = $plugin->sourceDefaults();
    }
    return $defaults;
  }

  /**
   * Writes to feeds log.
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
  }

  /**
   * Background job helper. Starts a background job using Job Scheduler.
   *
   * Execute the first batch chunk of a background job on the current page load,
   * moves the rest of the job processing to a cron powered background job.
   *
   * Executing the first batch chunk is important, otherwise, when a user
   * submits a source for import or clearing, we will leave her without any
   * visual indicators of an ongoing job.
   *
   * @see Feed::startImport().
   * @see Feed::startClear().
   *
   * @param $method
   *   Method to execute on importer; one of 'import' or 'clear'.
   *
   * @throws Exception $e
   */
  protected function startBackgroundJob($method) {
    if (FEEDS_BATCH_COMPLETE != $this->$method()) {
      $job = array(
        'type' => $this->getImporter()->id(),
        'id' => $this->id(),
        'period' => 0,
        'periodic' => FALSE,
      );
      JobScheduler::get("feeds_feed_{$method}")->set($job);
    }
  }

  /**
   * Batch API helper. Starts a Batch API job.
   *
   * @see Feed::startImport()
   * @see Feed::startClear()
   * @see feeds_batch()
   *
   * @param $title
   *   Title to show to user when executing batch.
   * @param $method
   *   Method to execute on importer; one of 'import' or 'clear'.
   */
  protected function startBatchAPIJob($title, $method) {
    $batch = array(
      'title' => $title,
      'operations' => array(
        array('feeds_batch', array($method, $this->id())),
      ),
    );
    batch_set($batch);
  }

  /**
   * Acquires a lock for this source.
   *
   * @throws FeedsLockException
   *   If a lock for the requested job could not be acquired.
   */
  protected function acquireLock() {
    if (!lock()->acquire("feeds_feed_{$this->id()}", 60.0)) {
      throw new FeedsLockException(t('Cannot acquire lock for feed @id / @fid.', array('@id' => $this->getImporter()->id(), '@fid' => $this->id())));
    }
  }

  /**
   * Releases a lock for this source.
   */
  protected function releaseLock() {
    lock()->release("feeds_feed_{$this->id()}");
  }

  /**
   * Similar to setConfig but adds to existing configuration.
   *
   * @param $config
   *   Array containing configuration information. Will be filtered by the keys
   *   returned by configDefaults().
   */
  public function addConfig($config) {
    $this->config = array_merge($this->config->value, $config);
    $default_keys = $this->configDefaults();
    $this->config = array_intersect_key($this->config->value, $default_keys);
  }

  /**
   * Implements getConfig().
   *
   * Return configuration array, ensure that all default values are present.
   */
  public function getConfig() {
    return $this->config->value;
  }

  /**
   * Set configuration.
   *
   * @param $config
   *   Array containing configuration information. Config array will be filtered
   *   by the keys returned by configDefaults() and populated with default
   *   values that are not included in $config.
   */
  public function setConfig($config) {
    $defaults = $this->configDefaults();
    $this->config = array_intersect_key($config, $defaults) + $defaults;
  }

  public function getUser() {
    return user_load($this->get('uid')->value);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    // $feed->state = isset($feed->state) ? $feed->state : FALSE;
    // $feed->fetcher_result = isset($feed->fetcher_result) ? $feed->fetcher_result : FALSE;
    // Before saving the feeds, set changed and revision times.
    $this->changed->value = REQUEST_TIME;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    // Alert implementers of FeedInterface to the fact that we're saving.
    foreach ($this->getImporter()->getPluginTypes() as $type) {
      $this->getImporter()->$type->sourceSave($this);
    }
    $config = $this->getConfig();

    // Store the source property of the fetcher in a separate column so that we
    // can do fast lookups on it.
    $this->source->value = '';
    if (isset($config[$this->getImporter()->fetcher->getPluginID()]['source'])) {
      $this->source = $config[$this->getImporter()->fetcher->getPluginID()]['source'];
    }

    // @todo move this to the storage controller.
    db_update('feeds_feed')
      ->condition('fid', $this->id())
      ->fields(array(
        'source' => $this->source->value,
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

    db_delete('feeds_log')
      ->condition('fid', $ids, 'IN')
      ->execute();

    // Alert plugins that we are deleting.
    foreach ($feeds as $feed) {
      foreach ($feed->getImporter()->getPluginTypes() as $type) {
        $feed->getImporter()->$type->sourceDelete($feed);
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
   * Unlocks a feed.
   *
   * @todo move this to the storage controller.
   */
  public function unlock() {
    db_update('feeds_feed')
      ->condition('fid', $this->id())
      ->fields(array('state' => FALSE))
      ->execute();
  }

}
