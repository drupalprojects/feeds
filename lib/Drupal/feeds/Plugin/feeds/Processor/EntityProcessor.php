<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Processor\EntityProcessor.
 */

namespace Drupal\feeds\Plugin\feeds\Processor;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedsParserResult;
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
class EntityProcessor extends ProcessorBase implements FormInterface {

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

  protected $handlers = array();

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    $this->pluginDefinition = $plugin_definition;
    $this->loadHandlers($configuration);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
   * @return string|NULL
   *   The bundle type this processor operates on, or NULL if it is undefined.
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
   * @return string|NULL
   *   The bundle type this processor operates on, or NULL if it is undefined.
   */
  public function bundle() {
    if ($bundle_key = $this->bundleKey()) {
      return $this->config['values'][$bundle_key];
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
    $values = $this->config['values'];
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

  public function getConfig($key = NULL) {
    $this->config + $this->apply('getConfig');

    if ($key) {
      if (isset($this->config[$key])) {
        return $this->config[$key];
      }

      return NULL;
    }

    return $this->config;
  }

  /**
   * {@inheritdoc}
   */
  public function configDefaults() {
    $bundle = key(entity_get_bundles($this->entityType()));

    $defaults = array(
      'values' => array(
        $this->bundleKey() => $bundle,
      ),
      'handlers' => array(),
      'expire' => FEEDS_EXPIRE_NEVER,
    ) + parent::configDefaults();

    $defaults += $this->apply('configDefaults');

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
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

    $tokens = array('@entities' => drupal_strtolower($info['label_plural']));

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
      '#default_value' => $this->config['update_existing'],
    );

    $form = parent::buildForm($form, $form_state);

    $this->apply('formAlter', $form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    $this->apply('validateForm', $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->apply('submitForm', $form, $form_state);
    parent::submitForm($form, $form_state);
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
    $definitions = \Drupal::service('plugin.manager.feeds.mapper')->getDefinitions();
    foreach ($definitions as $definition) {
      $mapper = \Drupal::service('plugin.manager.feeds.mapper')->createInstance($definition['id']);
      $mapper->targets($targets, $this->entityType(), $this->bundle());
    }

    return $targets;
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetElement(FeedInterface $feed, $entity, $target_element, $value) {
    $properties = $this->getProperties();
    if (isset($properties[$target_element])) {
      $entity->set($target_element, $value);
    }
    else {
      $this->apply('setTargetElement', $feed, $entity, $target_element, $value);
      parent::setTargetElement($feed, $entity, $target_element, $value);
    }
  }

  /**
   * Return expiry time.
   */
  public function expiryTime() {
    return $this->config['expire'];
  }

  protected function expiryQuery(FeedInterface $feed, $time) {
    $select = parent::expiryQuery($feed, $time);
    $this->apply('expiryQuery', $feed, $select, $time);
    return $select;
  }

  protected function existingEntityId(FeedInterface $feed, FeedsParserResult $result) {
    if ($id = parent::existingEntityId($feed, $result)) {
      return $id;
    }

    $ids = array_filter($this->apply('existingEntityId', $feed, $result));

    if ($ids) {
      return reset($ids);
    }

    return 0;
  }

}
