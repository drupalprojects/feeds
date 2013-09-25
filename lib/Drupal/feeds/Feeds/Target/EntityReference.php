<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Target\EntityReference.
 */

namespace Drupal\feeds\Feeds\Target;

use Drupal\Component\Utility\String as DrupalString;
use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines an entity reference mapper.
 *
 * @Plugin(
 *   id = "entity_reference",
 *   field_types = {"entity_reference", "entity_reference_field"}
 * )
 */
class EntityReference extends FieldTargetBase implements ConfigurableTargetInterface {

  /**
   * The entity being referenced.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The entity keys of the entity being referenced.
   *
   * @var array
   */
  protected $entityKeys;

  /**
   * The bundle being referenced.
   *
   * @var string
   */
  protected $bundle;

  /**
   * Referenceable entities.
   *
   * @var array
   */
  protected $availableEntities;

  /**
   * The entity key to use as a condition.
   *
   * @var string
   */
  protected $conditionKey;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $settings, $plugin_id, array $plugin_definition) {
    parent::__construct($settings, $plugin_id, $plugin_definition);
    // Calculate the upload directory.

    // $this->availableEntities = \Drupal::service('plugin.manager.entity_reference.selection')
    //   ->getInstance(array('field_definition' => $this->settings['instance']))
    //   ->getReferenceableEntities();

    $this->entityQuery = \Drupal::entityQuery($this->getEntityType());
  }

  protected function getPotentialFields() {
    $field_definitions = \Drupal::entityManager()->getFieldDefinitions($this->getEntityType());
    $field_definitions = array_filter($field_definitions, function($field) {
      return empty($field['configurable']) && empty($field['computed']) && $field['type'] != 'boolean_field';
    });
    $options = array();
    foreach ($field_definitions as $id => $definition) {
      $options[$id] = DrupalString::checkPlain($definition['label']);
    }

    return $options;
  }

  protected function getEntityType() {
    return $this->settings['settings']['target_type'];
  }

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(array &$target) {
    unset($target['properties']['revision_id']);
    unset($target['properties']['entity']);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    $values['target_id'] = $this->findEntity(trim($values['target_id']));
  }

  /**
   * Searches for an entity by entity key.
   *
   * @param string $value
   *   The value to search for.
   *
   * @return int|false
   *   The entity id, or false, if not found.
   */
  protected function findEntity($value) {
    $query = clone $this->entityQuery;

    if ($this->bundle) {
      $query->condition($this->entityKeys['bundle'], $this->bundle);
    }

    $ids = array_filter($query->condition($this->configuration['reference_by'], $value)->range(0, 1)->execute());
    if ($ids) {
      return reset($ids);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array('reference_by' => NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    $options = $this->getPotentialFields();

    $form['reference_by'] = array(
      '#type' => 'select',
      '#title' => $this->t('Reference by'),
      '#options' => $options,
      '#default_value' => $this->configuration['reference_by'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $options = $this->getPotentialFields();
    if ($this->configuration['reference_by'] && isset($options[$this->configuration['reference_by']])) {
      $options = $this->getPotentialFields();
      return $this->t('Reference by: %message', array('%message' => $options[$this->configuration['reference_by']]));
    }
    return $this->t('Please select a field to reference by.');
  }

}
