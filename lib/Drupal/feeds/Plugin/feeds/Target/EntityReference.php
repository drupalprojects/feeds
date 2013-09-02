<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Target\EntityReference.
 */

namespace Drupal\feeds\Plugin\feeds\Target;

use Drupal\Component\Annotation\Plugin;
use Drupal\feeds\Plugin\FieldTargetBase;
use Drupal\feeds\Plugin\ConfigurableTargetInterface;

/**
 * Defines an entity reference field mapper.
 *
 * @Plugin(
 *   id = "entity_reference",
 *   field_types = {"entity_reference_field", "entity_reference"}
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
    if (isset($this->settings['instance'])) {
      $this->entityType = $this->getEntityType();

      // $this->availableEntities = \Drupal::service('plugin.manager.entity_reference.selection')
      //   ->getInstance(array('field_definition' => $this->settings['instance']))
      //   ->getReferenceableEntities();

      $info = entity_get_info($this->entityType);
      $this->entityQuery = \Drupal::entityQuery($this->entityType);
      $this->conditionKey = $info['entity_keys'][$this->configuration['reference_by']];
    }
    else {
    }
  }

  protected function getEntityType() {
    return $this->settings['instance']->getFieldSetting('target_type');
  }

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(array &$target) {
    unset($target['properties']['revision_id']);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    $values['target_id'] = $this->findEntity(trim($values['value']));
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

    $ids = $query->condition($this->conditionKey, $value)->range(0, 1)->execute();
    if ($ids) {
      return reset($ids);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultConfiguration() {
    return array('reference_by' => 'label');
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    $options = array(
      'id' => $this->t('Entity id'),
      'label' => $this->t('Entity label'),
      'uuid' => $this->t('UUID'),
    );

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
    switch ($this->configuration['reference_by']) {
      case 'id':
        $message = 'Entity id';
        break;

      case 'label':
        $message = 'Entity label';
        break;

      case 'uuid':
        $message = 'Entity UUID';
        break;
    }

    return $this->t('Reference by: %message', array('%message' => $message));
  }

}
