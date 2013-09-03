<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Processor\EntityProcessor.
 */

namespace Drupal\feeds\Feeds\Processor;

use Drupal\Component\Annotation\Plugin;
use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Entity\Field\FieldItemInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\feeds\Exception\EntityAccessException;
use Drupal\feeds\Exception\ValidationException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\AdvancedFormPluginInterface;
use Drupal\feeds\Plugin\Type\ClearableInterface;
use Drupal\feeds\Plugin\Type\ConfigurablePluginBase;
use Drupal\feeds\Plugin\Type\LockableInterface;
use Drupal\feeds\Plugin\Type\Processor\ProcessorInterface;
use Drupal\feeds\Plugin\Type\Scheduler\SchedulerInterface;
use Drupal\feeds\Result\ParserResultInterface;
use Drupal\feeds\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines an entity processor.
 *
 * Creates entities from feed items.
 *
 * @Plugin(
 *   id = "entity",
 *   title = @Translation("Entity processor"),
 *   description = @Translation("Creates entities from feed items."),
 *   derivative = "\Drupal\feeds\Plugin\Derivative\EntityProcessor"
 * )
 */
class EntityProcessor extends ConfigurablePluginBase implements ProcessorInterface, ClearableInterface, LockableInterface, AdvancedFormPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The entity storage controller for the entity type being processed.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $storageController;

  /**
   * The entity query factory object.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * The entity info for the selected entity type.
   *
   * @var array
   */
  protected $entityInfo;

  /**
   * The targets for this processor.
   *
   * @var array
   */
  protected $targets = array();

  /**
   * The extenders that apply to this entity type.
   *
   * @var array
   */
  protected $handlers = array();

  /**
   * Whether or not we should continue processing existing items.
   *
   * @var bool
   */
  protected $skipExisting;

  /**
   * Flag indicating that this processor is locked.
   *
   * @var bool
   */
  protected $isLocked;

  /**
   * Constructs an EntityProcessor object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $storage_controller
   *   The storage controller for this processor.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, array $entity_info, EntityStorageControllerInterface $storage_controller, QueryFactory $query_factory) {
    // $entityInfo has to be assinged before $this->loadHandlers() is called.
    $this->entityInfo = $entity_info;
    $this->storageController = $storage_controller;
    $this->queryFactory = $query_factory;
    $this->pluginDefinition = $plugin_definition;

    $this->loadHandlers($configuration);

    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->skipExisting = $this->configuration['update_existing'] == ProcessorInterface::SKIP_EXISTING;

    // Let handlers modify the entity info.
    $this->apply('entityInfo', $this->entityInfo);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    $entity_manager = $container->get('entity.manager');
    $entity_info = $entity_manager->getDefinition($plugin_definition['entity type']);
    $storage_controller = $entity_manager->getStorageController($plugin_definition['entity type']);

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entity_info,
      $storage_controller,
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @todo Bulk load existing entity ids/entities.
   */
  public function process(FeedInterface $feed, StateInterface $state, ParserResultInterface $parser_result) {
    while ($item = $parser_result->shiftItem()) {
      $this->processItem($feed, $state, $item);
    }
  }

  /**
   * Processes a single item.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed being processed.
   * @param \Drupal\feeds\StateInterface $state
   *   The state object.
   * @param array $item
   *   The item being processed.
   */
  protected function processItem(FeedInterface $feed, StateInterface $state, array $item) {
    // Check if this item already exists.
    $existing_entity_id = $this->existingEntityId($feed, $item);

    // If it exists, and we are not updating, pass onto the next item.
    if ($existing_entity_id && $this->skipExisting) {
      return;
    }

    if ($existing_entity_id) {
      $entity = $this->entityLoad($existing_entity_id);
    }

    $hash = $this->hash($item);
    $changed = $existing_entity_id && $hash === $entity->get('feeds_item')->hash;

    // Do not proceed if the item exists, has not changed, and we're not
    // forcing the update.
    if ($existing_entity_id && !$changed && !$this->configuration['skip_hash_check']) {
      return;
    }

    try {
      // Build a new entity.
      if (!$existing_entity_id) {
        $entity = $this->newEntity($feed);
      }

      // Set property and field values.
      $this->map($feed, $entity, $item);
      $this->entityValidate($entity);

      // This will throw an exception on failure.
      $this->entitySaveAccess($entity);

      // Set the values that we absolutely need.
      $entity->get('feeds_item')->target_id = $feed->id();
      $entity->get('feeds_item')->hash = $hash;

      // And... Save! We made it.
      $entity->save();

      // Track progress.
      $existing_entity_id ? $state->updated++ : $state->created++;
    }

    // Something bad happened, log it.
    catch (\Exception $e) {
      $state->failed++;
      drupal_set_message($e->getMessage(), 'warning');
      $message = $this->createLogMessage($e, $entity, $item);
      $feed->log('import', $message, array(), WATCHDOG_ERROR);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clear(FeedInterface $feed) {
    $state = $feed->state(StateInterface::CLEAR);

    // Build base select statement.
    $query = $this->queryFactory->get($this->entityType())
      ->condition('feeds_item.target_id', $feed->id());

    // If there is no total, query it.
    if (!$state->total) {
      $count_query = clone $query;
      $state->total = $count_query->count()->execute();
    }

    // Delete a batch of entities.
    $entity_ids = $query->range(0, $this->getLimit())->execute();

    if ($entity_ids) {
      $this->entityDeleteMultiple($entity_ids);
      $state->deleted += count($entity_ids);
      $state->progress($state->total, $state->deleted);
    }
    else {
      $state->progress($state->total, $state->total);
    }

    // Report results when done.
    if ($feed->progressClearing() == StateInterface::BATCH_COMPLETE) {
      if ($state->deleted) {
        $message = format_plural(
          $state->deleted,
          'Deleted @number @entity from %title.',
          'Deleted @number @entities from %title.',
          array(
            '@number' => $state->deleted,
            '@entity' => Unicode::strtolower($this->entityLabel()),
            '@entities' => Unicode::strtolower($this->entityLabelPlural()),
            '%title' => $feed->label(),
          )
        );
        $feed->log('clear', $message, array(), WATCHDOG_INFO);
        drupal_set_message($message);
      }
      else {
        drupal_set_message($this->t('There are no @entities to delete.', array('@entities' => Unicode::strtolower($this->entityLabelPlural()))));
      }
    }
  }

  /**
   * Called after processing all items to display messages.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed being processed.
   */
  public function setMessages(FeedInterface $feed) {
    $state = $feed->state(StateInterface::PROCESS);

    $tokens = array(
      '@entity' => Unicode::strtolower($this->entityLabel()),
      '@entities' => Unicode::strtolower($this->entityLabelPlural()),
    );

    $messages = array();

    if ($state->created) {
      $messages[] = array(
        'message' => format_plural(
          $state->created,
          'Created @number @entity.',
          'Created @number @entities.',
          array('@number' => $state->created) + $tokens
        ),
      );
    }
    if ($state->updated) {
      $messages[] = array(
        'message' => format_plural(
          $state->updated,
          'Updated @number @entity.',
          'Updated @number @entities.',
          array('@number' => $state->updated) + $tokens
        ),
      );
    }
    if ($state->failed) {
      $messages[] = array(
        'message' => format_plural(
          $state->failed,
          'Failed importing @number @entity.',
          'Failed importing @number @entities.',
          array('@number' => $state->failed) + $tokens
        ),
        'level' => WATCHDOG_ERROR,
      );
    }
    if (!$messages) {
      $messages[] = array(
        'message' => $this->t('There are no new @entities.', array('@entities' => Unicode::strtolower($this->entityLabelPlural()))),
      );
    }
    foreach ($messages as $message) {
      drupal_set_message($message['message']);
      $feed->log('import', $message['message'], array(), isset($message['level']) ? $message['level'] : WATCHDOG_INFO);
    }
  }

  /**
   * Loads the handlers that apply to this processor.
   *
   * @todo Move this to PluginBase.
   */
  protected function loadHandlers(array $configuration) {
    $definitions = \Drupal::service('plugin.manager.feeds.handler')->getDefinitions();
    foreach ($definitions as $definition) {
      $class = $definition['class'];
      if ($class::applies($this)) {
        $this->handlers[] = \Drupal::service('plugin.manager.feeds.handler')->createInstance($definition['id'], $configuration);
      }
    }
  }

  /**
   * Applies a function to listeners.
   *
   * @todo Move to PluginBase.
   *
   * @todo Events?
   */
  protected function apply($action, &$arg1 = NULL, &$arg2 = NULL, &$arg3 = NULL, &$arg4 = NULL) {
    $return = array();

    foreach ($this->handlers as $handler) {
      if (method_exists($handler, $action)) {
        $callable = array($handler, $action);
        $result = $callable($arg1, $arg2, $arg3, $arg4);
        if (is_array($result)) {
          $return = array_merge($return, $result);
        }
        else {
          $return[] = $result;
        }
      }
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function entityType() {
    return $this->pluginDefinition['entity type'];
  }

  /**
   * Bundle type this processor operates on.
   *
   * Defaults to the entity type for entities that do not define bundles.
   *
   * @return string|null
   *   The bundle type this processor operates on, or null if it is undefined.
   */
  protected function bundleKey() {
    if (!empty($this->entityInfo['entity_keys']['bundle'])) {
      return $this->entityInfo['entity_keys']['bundle'];
    }
  }

  /**
   * Bundle type this processor operates on.
   *
   * Defaults to the entity type for entities that do not define bundles.
   *
   * @return string|null
   *   The bundle type this processor operates on, or null if it is undefined.
   *
   * @todo We should be more careful about missing bundles.
   */
  public function bundle() {
    if ($bundle_key = $this->bundleKey()) {
      if (isset($this->configuration['values'][$bundle_key])) {
        return $this->configuration['values'][$bundle_key];
      }
      return;
    }

    return $this->entityType();
  }

  /**
   * Returns the bundle label for the entity being processed.
   *
   * @return string
   *   The bundle label.
   */
  protected function bundleLabel() {
    if (!empty($this->entityInfo['bundle_label'])) {
      return $this->entityInfo['bundle_label'];
    }

    return $this->t('Bundle');
  }

  /**
   * Provides a list of bundle options for use in select lists.
   *
   * @return array
   *   A keyed array of bundle => label.
   */
  protected function bundleOptions() {
    $options = array();
    foreach (entity_get_bundles($this->entityType()) as $bundle => $info) {
      if (!empty($info['label'])) {
        $options[$bundle] = $info['label'];
      }
      else {
        $options[$bundle] = $bundle;
      }
    }

    return $options;
  }

  /**
   * Returns the label of the entity being processed.
   *
   * @return string
   *   The label of the entity.
   */
  protected function entityLabel() {
    return $this->entityInfo['label'];
  }

  /**
   * Returns the plural label of the entity being processed.
   *
   * This will return the singular label if the plural label does not exist.
   *
   * @return string
   *   The plural label of the entity.
   */
  protected function entityLabelPlural() {
    if (!empty($this->entityInfo['label_plural'])) {
      return $this->entityInfo['label_plural'];
    }
    return $this->entityInfo['label'];
  }

  /**
   * {@inheritdoc}
   */
  protected function newEntity(FeedInterface $feed) {
    $values = $this->configuration['values'];
    $values = $this->apply('newEntityValues', $feed, $values);
    return $this->storageController->create($values)->getNGEntity();
  }

  /**
   * {@inheritdoc}
   */
  protected function entityLoad(FeedInterface $feed, $entity_id) {
    $entity = $this->storageController->load($entity_id)->getNGEntity();
    $this->apply('entityPrepare', $feed, $entity);

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function entityValidate(EntityInterface $entity) {
    $this->apply(__FUNCTION__, $entity);

    $violations = $entity->validate();
    if (count($violations)) {
      $args = array(
        '@entity' => Unicode::strtolower($this->entityLabel()),
        '%label' => $entity->label(),
        '@url' => $this->url('feeds_importer.mapping', array('feeds_importer' => $this->importer->id())),
      );
      throw new ValidationException(String::format('The @entity %label failed to validate. Please check your <a href="@url">mappings</a>.', $args));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function entitySaveAccess(EntityInterface $entity) {
    if ($this->configuration['authorize'] && !empty($entity->uid->value)) {

      // If the uid was mapped directly, rather than by email or username, it
      // could be invalid.
      if (!($account = $entity->uid->entity)) {
        $message = 'User %uid is not a valid user.';
        throw new EntityAccessException(String::format($message, array('%uid' => $entity->uid->value)));
      }

      $op = $entity->isNew() ? 'create' : 'update';

      if (!$entity->access($op, $account)) {
        $args = array(
          '%name' => $account->getUsername(),
          '%op' => $op,
          '@bundle' => Unicode::strtolower($this->bundleLabel()),
          '%bundle' => $entity->bundle(),
        );
        throw new EntityAccessException(String::format('User %name is not authorized to %op @bundle %bundle.', $args));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function entityDeleteMultiple(array $entity_ids) {
    $entities = $this->storageController->loadMultiple($entity_ids);
    $this->storageController->delete($entities);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultConfiguration() {
    $defaults = array(
      'update_existing' => ProcessorInterface::SKIP_EXISTING,
      'skip_hash_check' => FALSE,
      'values' => array($this->bundleKey() => NULL),
      'authorize' => TRUE,
      'expire' => SchedulerInterface::EXPIRE_NEVER,
    );

    $defaults += $this->apply(__FUNCTION__);

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    $tokens = array('@entity' => Unicode::strtolower($this->entityLabel()), '@entities' => Unicode::strtolower($this->entityLabelPlural()));

    $form['update_existing'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Update existing @entities', $tokens),
      '#description' => $this->t('Existing @entities will be determined using mappings that are a "unique target".', $tokens),
      '#options' => array(
        ProcessorInterface::SKIP_EXISTING => $this->t('Do not update existing @entities', $tokens),
        ProcessorInterface::REPLACE_EXISTING => $this->t('Replace existing @entities', $tokens),
        ProcessorInterface::UPDATE_EXISTING => $this->t('Update existing @entities', $tokens),
      ),
      '#default_value' => $this->configuration['update_existing'],
    );
    $form['authorize'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Authorize'),
      '#description' => $this->t('Check that the author has permission to create the @entity.', $tokens),
      '#default_value' => $this->configuration['authorize'],
    );
    $form['skip_hash_check'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Force update'),
      '#description' => $this->t('Forces the update of items even if the feed did not change.'),
      '#default_value' => $this->configuration['skip_hash_check'],
    );
    $period = drupal_map_assoc(array(SchedulerInterface::EXPIRE_NEVER, 3600, 10800, 21600, 43200, 86400, 259200, 604800, 2592000, 2592000 * 3, 2592000 * 6, 31536000), array($this, 'formatExpire'));
    $form['expire'] = array(
      '#type' => 'select',
      '#title' => $this->t('Expire @entities', $tokens),
      '#options' => $period,
      '#description' => $this->t('Select after how much time @entities should be deleted.', $tokens),
      '#default_value' => $this->configuration['expire'],
    );

    $this->apply(__FUNCTION__, $form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, array &$form_state) {
    $this->apply(__FUNCTION__, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @todo We need an importer save/update/delete API.
   */
  public function submitConfigurationForm(array &$form, array &$form_state) {
    $this->apply(__FUNCTION__, $form, $form_state);

    $values =& $form_state['values']['processor']['configuration'];
    if ($this->configuration['expire'] != $values['expire']) {
      $this->importer->reschedule($this->importer->id());
    }

    parent::submitConfigurationForm($form, $form_state);
    $this->prepareFeedsItemField();
  }

  /**
   * Prepares the feeds_item field.
   *
   * @todo How does ::load() behave for deleted fields?
   */
  protected function prepareFeedsItemField() {
    // Make sure our field exists.
    $entity_type = $this->entityType();
    $bundle = $this->bundle();
    // Create the field and instance.
    $field_storage = \Drupal::entityManager()->getStorageController('field_entity');
    $instance_storage = \Drupal::entityManager()->getStorageController('field_instance');

    // Create field if it doesn't exist.
    if (!$field_storage->load("$entity_type.feeds_item")) {
      $field_storage->create(array(
        'name' => 'feeds_item',
        'entity_type' => $entity_type,
        'type' => 'feeds_item',
        'translatable' => FALSE,
      ))->save();
    }
    // Create field instance if it doesn't exist.
    if (!$instance_storage->load("$entity_type.$bundle.feeds_item")) {
      $instance_storage->create(array(
        'field_name' => 'feeds_item',
        'entity_type' => $entity_type,
        'bundle' => $bundle,
        'label' => 'Feeds item',
      ))->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingTargets() {
    if (!$this->targets) {
      // Let other modules expose mapping targets.
      $definitions = \Drupal::service('plugin.manager.feeds.target')->getDefinitions();

      foreach ($definitions as $definition) {
        $class = $definition['class'];
        $class::targets($this->targets, $this->importer, $definition);
      }

      $this->apply(__FUNCTION__, $this->targets);
    }

    return $this->targets;
  }

  /**
   * {@inheritdoc}
   */
  public function expiryTime() {
    return $this->configuration['expire'];
  }

  /**
   * {@inheritdoc}
   */
  public function expire(FeedInterface $feed, $time = NULL) {
    $state = $feed->state(StateInterface::EXPIRE);

    if ($time === NULL) {
      $time = $this->expiryTime();
    }
    if ($time == SchedulerInterface::EXPIRE_NEVER) {
      return;
    }

    $query = $this->queryFactory->get($this->entityType())
      ->condition('feeds_item.target_id', $feed->fid())
      ->condition('feeds_item.imported', REQUEST_TIME - $time, '<');

    // If there is no total, query it.
    if (!$state->total) {
      $count_query = clone $query;
      $state->total = $count_query->count()->execute();
    }

    // Delete a batch of entities.
    if ($entity_ids = $query->range(0, $this->getLimit())->execute()) {
      $this->entityDeleteMultiple($entity_ids);
      $state->deleted += count($entity_ids);
      $state->progress($state->total, $state->deleted);
    }
    else {
      $state->progress($state->total, $state->total);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getItemCount(FeedInterface $feed) {
    return $this->queryFactory->get($this->entityType())
      ->condition('feeds_item.target_id', $feed->id())
      ->count()
      ->execute();
  }

  /**
   * Returns an existing entity id.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed being processed.
   * @param array $item
   *   The item to find existing ids for.
   *
   * @return int|false
   *   The integer of the entity, or false if not found.
   */
  protected function existingEntityId(FeedInterface $feed, array $item) {
    $query = $this->queryFactory->get($this->entityType())
      ->condition('feeds_item.target_id', $feed->id())
      ->range(0, 1);

    // Iterate through all unique targets and test whether they do already
    // exist in the database.
    foreach ($this->uniqueTargets($feed, $item) as $target => $value) {
      $entity_id = $query->condition($target, $value)->execute();

      if ($entity_id) {
        return key($entity_id);
      }

      $query = clone $query;
    }

    return FALSE;
  }

  /**
   * Iterates over a target array and retrieves all sources that are unique.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed being imported.
   * @param array $item
   *   The parser result object.
   *
   * @return array
   *   An array where the keys are target field names and the values are the
   *   elements from the source item mapped to these targets.
   */
  protected function uniqueTargets(FeedInterface $feed, array $item) {
    $parser = $this->importer->getParser();
    $targets = array();

    foreach ($this->importer->getMappings() as $mapping) {
      if (!empty($mapping['unique'])) {
        foreach ($mapping['unique'] as $source => $true) {
          // Invoke the parser's getSourceElement to retrieve the value for this
          // mapping's source.
          $field = $mapping['target'] . '.' . $mapping['map'][$source];
          $targets[$field] = $parser->getSourceElement($feed, $item, $source);
        }
      }
    }

    return $targets;
  }

  /**
   * {@inheritdoc}
   */
  public function buildAdvancedForm(array $form, array &$form_state) {
    $form['values']['#tree'] = TRUE;

    if ($bundle_key = $this->bundleKey()) {
      $form['values'][$bundle_key] = array(
        '#type' => 'select',
        '#options' => $this->bundleOptions(),
        '#title' => $this->bundleLabel(),
        '#required' => TRUE,
        '#default_value' => $this->bundle(),
        '#disabled' => $this->isLocked(),
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @todo We could make this smarter and check if any feeds have items
   *   imported.
   */
  public function isLocked() {
    if ($this->isLocked === NULL) {
      $this->isLocked = (bool) $this->queryFactory
        ->get('feeds_feed')
        ->condition('importer', $this->importer->id())
        ->range(0, 1)
        ->execute();
    }

    return $this->isLocked;
  }

  /**
   * Creates an MD5 hash of an item.
   *
   * Includes mappings so that items will be updated if the mapping
   * configuration has changed.
   *
   * @param array $item
   *   The item to hash.
   *
   * @return string
   *   Always returns a hash, even with empty, null, or false:
   *   - Empty arrays return 40cd750bba9870f18aada2478b24840a
   *   - Empty/NULL/FALSE strings return d41d8cd98f00b204e9800998ecf8427e
   *
   * @todo I really doubt the above is still true. Plus, who cares.
   */
  protected function hash(array $item) {
    return hash('md5', serialize($item) . serialize($this->importer->getMappings()));
  }

  /**
   * Creates a log message when an exception occured during import.
   *
   * @param \Exception $e
   *   The exception that was thrown during processing.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object that was being processed.
   * @param arary $item
   *   The parser result for this entity.
   *
   * @return string
   *   The message to log.
   *
   * @todo This no longer works due to circular references.
   * @todo Move to EntityProcessor.
   */
  protected function createLogMessage(\Exception $e, EntityInterface $entity, array $item) {
    include_once DRUPAL_ROOT . '/core/includes/utility.inc';
    $message = $e->getMessage();
    $message .= '<h3>Original item</h3>';
    $message .= '<pre>' . drupal_var_export($item) . '</pre>';
    $message .= '<h3>Entity</h3>';
    $message .= '<pre>' . drupal_var_export($entity->getValue()) . '</pre>';
    return $message;
  }

  /**
   * Formats UNIX timestamps to readable strings.
   *
   * @param int $timestamp
   *   A UNIX timestamp.
   *
   * @return string
   *   A string in the format, "After (time)" or "Never."
   */
  public function formatExpire($timestamp) {
    if ($timestamp == SchedulerInterface::EXPIRE_NEVER) {
      return $this->t('Never');
    }
    return $this->t('after !time', array('!time' => format_interval($timestamp)));
  }

  /**
   * {@inheritdoc}
   *
   * @todo Get rid of the variable_get() here.
   */
  public function getLimit() {
    return variable_get('feeds_process_limit', ProcessorInterface::PROCESS_LIMIT);
  }

  /**
   * Execute mapping on an item.
   *
   * This method encapsulates the central mapping functionality. When an item is
   * processed, it is passed through map() where the properties of $source_item
   * are mapped onto $target_item following the processor's mapping
   * configuration.
   *
   * For each mapping ParserInterface::getSourceElement() is executed to
   * retrieve the source element, then ProcessorBase::setTargetElement() is
   * invoked to populate the target item properly.
   */
  protected function map(FeedInterface $feed, EntityInterface $entity, array $item) {
    $parser = $this->importer->getParser();
    $sources = $parser->getMappingSources();
    $targets = $this->getMappingTargets();

    // Mappers add to existing fields rather than replacing them. Hence we need
    // to clear target elements of each item before mapping in case we are
    // mapping on a prepopulated item such as an existing node.
    foreach ($this->importer->getMappings() as $mapping) {
      unset($entity->{$mapping['target']});
    }

    // Gather all of the values values for this item.
    $values = array();
    foreach ($this->importer->getMappings() as $mapping) {
      $target = $mapping['target'];

      foreach ($mapping['map'] as $column => $source) {

        if (!isset($values[$target][$column])) {
          $values[$target][$column] = array();
        }

        // Retrieve source element's value from parser.
        $value = $parser->getSourceElement($feed, $item, $source);
        if (!is_array($value)) {
          $values[$target][$column][] = $value;
        }
        else {
          $values[$target][$column] = array_merge($values[$target][$column], $value);
        }
      }
    }

    // Rearrange values into Drupal's field structure.
    $new_values = array();
    foreach ($values as $target => $value) {
      foreach ($value as $column => $v) {
        $delta = 0;
        foreach ($v as $avalue) {
          $new_values[$target][$delta][$column] = $avalue;
          $delta++;
        }
      }
    }

    // Set target values.
    foreach ($this->importer->getMappings() as $delta => $mapping) {
      $target = $mapping['target'];
      // Map the source element's value to the target.
      if ($plugin = $this->importer->getTargetPlugin($delta)) {
        $plugin->prepareValues($new_values[$target]);
      }
      // Set the values on the entity.
      $entity->get($target)->setValue($new_values[$target]);
    }

    return $entity;
  }

}
