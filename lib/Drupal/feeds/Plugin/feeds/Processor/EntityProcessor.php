<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Processor\EntityProcessor.
 */

namespace Drupal\feeds\Plugin\feeds\Processor;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormInterface;
use Drupal\feeds\AdvancedFormPluginInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Result\ParserResultInterface;
use Drupal\feeds\Plugin\ProcessorBase;

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
class EntityProcessor extends ProcessorBase implements AdvancedFormPluginInterface {

  /**
   * The plugin definition.
   *
   * @var array
   */
  protected $pluginDefinition;

  /**
   * The entity info for the selected entity type.
   *
   * @var array
   */
  protected $entityInfo;

  /**
   * The properties for this entity.
   *
   * @var array
   */
  protected $properties;

  /**
   * The extenders that apply to this entity type.
   *
   * @var array
   */
  protected $handlers = array();

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    $this->pluginDefinition = $plugin_definition;
    $this->loadHandlers($configuration);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function process(FeedInterface $feed, ParserResultInterface $parser_result) {
    $state = $feed->state(FEEDS_PROCESS);

    while ($item = $parser_result->shiftItem()) {

      // Check if this item already exists.
      $entity_id = $this->existingEntityId($feed, $parser_result);
      $skip_existing = $this->configuration['update_existing'] == FEEDS_SKIP_EXISTING;

      module_invoke_all('feeds_before_update', $feed, $item, $entity_id);

      // If it exists, and we are not updating, pass onto the next item.
      if ($entity_id && $skip_existing) {
        continue;
      }

      $hash = $this->hash($item);
      $changed = ($hash !== $this->getHash($entity_id));
      $force_update = $this->configuration['skip_hash_check'];

      // Do not proceed if the item exists, has not changed, and we're not
      // forcing the update.
      if ($entity_id && !$changed && !$force_update) {
        continue;
      }

      try {

        // Load an existing entity.
        if ($entity_id) {
          $entity = $this->entityLoad($feed, $entity_id);

          // The feeds_item table is always updated with the info for the most
          // recently processed entity. The only carryover is the entity_id.
          $item_info = $this->newItemInfo($entity, $feed, $hash);
          $item_info->entityId = $entity_id;
          $item_info->isNew = FALSE;
        }

        // Build a new entity.
        else {
          $entity = $this->newEntity($feed);
          $item_info = $this->newItemInfo($entity, $feed, $hash);
        }

        // Set property and field values.
        $this->map($feed, $item, $entity, $item_info);
        $this->entityValidate($entity);

        // Allow modules to alter the entity before saving.
        module_invoke_all('feeds_presave', $feed, $entity, $item, $item_info);

        // Enable modules to skip saving at all.
        if (!empty($item_info->skip)) {
          continue;
        }

        // This will throw an exception on failure.
        $this->entitySaveAccess($entity);
        $this->entitySave($entity);

        $item_info->entityId = $entity->id();
        \Drupal::service('feeds.item_info')->save($item_info);

        // Allow modules to perform operations using the saved entity data.
        // $entity contains the updated entity after saving.
        module_invoke_all('feeds_after_save', $feed, $entity, $item, $entity_id);

        // Track progress.
        if (empty($entity_id)) {
          $state->created++;
        }
        else {
          $state->updated++;
        }
      }

      // Something bad happened, log it.
      catch (\Exception $e) {
        $state->failed++;
        drupal_set_message($e->getMessage(), 'warning');
        $message = $this->createLogMessage($e, $entity, $item);
        $feed->log('import', $message, array(), WATCHDOG_ERROR);
      }
    }

    // Set messages if we're done.
    if ($feed->progressImporting() != FEEDS_BATCH_COMPLETE) {
      return;
    }

    $info = $this->entityInfo();
    $tokens = array(
      '@entity' => strtolower($info['label']),
      '@entities' => strtolower($info['label_plural']),
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
    if (empty($messages)) {
      $messages[] = array(
        'message' => t('There are no new @entities.', array('@entities' => strtolower($info['label_plural']))),
      );
    }
    foreach ($messages as $message) {
      drupal_set_message($message['message']);
      $feed->log('import', $message['message'], array(), isset($message['level']) ? $message['level'] : WATCHDOG_INFO);
    }
  }

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
   * Returns a new item info object.
   *
   * This is used to track entities created by Feeds.
   *
   * @param $entity
   *   The entity object to be populated with new item info.
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed that produces this entity.
   * @param $hash
   *   The fingerprint of the feed item.
   */
  protected function newItemInfo($entity, FeedInterface $feed, $hash = '') {
    $item_info = new \stdClass();
    $item_info->isNew = TRUE;
    $item_info->entityType = $entity->entityType();
    $item_info->fid = $feed->id();
    $item_info->imported = REQUEST_TIME;
    $item_info->hash = $hash;
    $item_info->url = '';
    $item_info->guid = '';

    return $item_info;
  }

  public function apply($action, &$arg1 = NULL, &$arg2 = NULL, &$arg3 = NULL, &$arg4 = NULL) {
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
   * {@inheritdoc}
   */
  protected function entityInfo() {
    if (!isset($this->entityInfo)) {
      $this->entityInfo = entity_get_info($this->entityType());
    }

    $this->apply('entityInfoAlter', $this->entityInfo);

    return $this->entityInfo;
  }

  /**
   * Bundle type this processor operates on.
   *
   * Defaults to the entity type for entities that do not define bundles.
   *
   * @return string|null
   *   The bundle type this processor operates on, or null if it is undefined.
   */
  public function bundleKey() {
    $info = $this->entityInfo();
    if (!empty($info['entity_keys']['bundle'])) {
      return $info['entity_keys']['bundle'];
    }
  }

  /**
   * Bundle type this processor operates on.
   *
   * Defaults to the entity type for entities that do not define bundles.
   *
   * @return string|null
   *   The bundle type this processor operates on, or null if it is undefined.
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
   * Provides a list of bundle options for use in select lists.
   *
   * @return array
   *   A keyed array of bundle => label.
   */
  public function bundleOptions() {
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

  public function getProperties() {
    if (!isset($this->properties)) {
      $fields = \Drupal::entityManager()->getFieldDefinitions($this->entityType(), $this->bundle());

      $this->properties = array();
      foreach ($fields as $id => $field) {
        if (empty($field['configurable']) && empty($field['read-only'])) {
          $this->properties[$id] = $field;
        }
      }
    }

    return $this->properties;
  }

  /**
   * {@inheritdoc}
   */
  protected function newEntity(FeedInterface $feed) {
    $values = $this->configuration['values'];
    $this->apply('newEntityValues', $feed, $values);
    return entity_create($this->entityType(), $values)->getBCEntity();
  }

  /**
   * {@inheritdoc}
   */
  protected function entityLoad(FeedInterface $feed, $entity_id) {
    $entity = entity_load($this->entityType(), $entity_id)->getBCEntity();
    $this->apply('entityPrepare', $feed, $entity);

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function entityValidate($entity) {
    $this->apply('entityValidate', $entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function entitySaveAccess($entity) {
    $this->apply('entitySaveAccess', $entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function entitySave($entity) {
    $this->apply('entityPreSave', $entity);
    $entity->save();
    $this->apply('entityPostSave', $entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function entityDeleteMultiple($entity_ids) {
    entity_delete_multiple($this->entityType(), $entity_ids);
    $this->apply('entityDeleteMultiple', $entity_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration($key = NULL) {
    $this->configuration + $this->apply('getConfiguration');
    return parent::getConfiguration($key);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultConfiguration() {
    $defaults = array(
      'values' => array(
        $this->bundleKey() => NULL,
      ),
      'handlers' => array(),
      'expire' => FEEDS_EXPIRE_NEVER,
    ) + parent::getDefaultConfiguration();

    $defaults += $this->apply(__FUNCTION__);

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    $info = $this->entityInfo();

    $label_plural = isset($info['label_plural']) ? $info['label_plural'] : $info['label'];
    $tokens = array('@entities' => drupal_strtolower($label_plural));

    $form['update_existing'] = array(
      '#type' => 'radios',
      '#title' => t('Update existing @entities', $tokens),
      '#description' =>
        t('Existing @entities will be determined using mappings that are a "unique target".', $tokens),
      '#options' => array(
        FEEDS_SKIP_EXISTING => t('Do not update existing @entities', $tokens),
        FEEDS_REPLACE_EXISTING => t('Replace existing @entities', $tokens),
        FEEDS_UPDATE_EXISTING => t('Update existing @entities', $tokens),
      ),
      '#default_value' => $this->configuration['update_existing'],
    );

    $form = parent::buildForm($form, $form_state);

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
   */
  public function submitConfigurationForm(array &$form, array &$form_state) {
    $this->apply(__FUNCTION__, $form, $form_state);
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingTargets() {

    // The bundle has not been selected.
    if (!$this->bundle()) {
      $info = $this->entityInfo();
      $bundle_name = !empty($info['bundle_name']) ? drupal_strtolower($info['bundle_name']) : t('bundle');
      $url = url('admin/structure/feeds/manage/' . $this->importer->id() . '/settings/processor');
      drupal_set_message(t('Please <a href="@url">select a @bundle_name</a>.', array('@url' => $url, '@bundle_name' => $bundle_name)), 'warning', FALSE);
    }

    $targets = parent::getMappingTargets();

    foreach ($this->getProperties() as $id => $field) {
      $targets[$id] = array(
        'name' => $field['label'],
        'description' => isset($field['description']) ? $field['description'] : '',
      );
    }

    $this->apply('getMappingTargets', $targets);

    // Let other modules expose mapping targets.
    $definitions = \Drupal::service('plugin.manager.feeds.target')->getDefinitions();
    foreach ($definitions as $definition) {
      $mapper = \Drupal::service('plugin.manager.feeds.target')->createInstance($definition['id'], array('importer' => $this->importer));
      $targets += $mapper->targets();
    }

    $new_targets = array();

    foreach ($targets as $key => $target) {
      if (empty($target['columns'])) {
        $new_targets[$key] = $target;
      }
      else {
        foreach ($target['columns'] as $column) {
          $new_targets[$key . ':' . $column] = $target;
        }
      }
    }

    return $new_targets;
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetElement(FeedInterface $feed, $entity, $target_element, $values, $mapping, \stdClass $item_info) {
      $properties = $this->getProperties();
      if (isset($properties[$target_element])) {
        $entity->get($target_element)->setValue($values[0]['value']);
      }
      else {
        $this->apply('setTargetElement', $feed, $entity, $target_element, $values[0]['value'], $mapping, $item_info);
        parent::setTargetElement($feed, $entity, $target_element, $values[0]['value'], $mapping, $item_info);
      }
  }

  /**
   * Return expiry time.
   */
  public function expiryTime() {
    return $this->configuration['expire'];
  }

  protected function expiryQuery(FeedInterface $feed, $time) {
    $select = parent::expiryQuery($feed, $time);
    $this->apply('expiryQuery', $feed, $select, $time);
    return $select;
  }

  protected function existingEntityId(FeedInterface $feed, ParserResultInterface $result) {
    if ($id = parent::existingEntityId($feed, $result)) {
      return $id;
    }

    $ids = array_filter($this->apply('existingEntityId', $feed, $result));

    if ($ids) {
      return reset($ids);
    }

    return 0;
  }

  public function buildAdvancedForm(array $form, array &$form_state) {
    $info = $this->entityInfo();

    $form['values']['#tree'] = TRUE;
    if ($bundle_key = $this->bundleKey()) {
      $form['values'][$bundle_key] = array(
        '#type' => 'select',
        '#options' => $this->bundleOptions(),
        '#title' => !empty($info['bundle_label']) ? $info['bundle_label'] : t('Bundle'),
        '#required' => TRUE,
        '#default_value' => $this->bundle(),
      );
    }

    return $form;
  }

}
