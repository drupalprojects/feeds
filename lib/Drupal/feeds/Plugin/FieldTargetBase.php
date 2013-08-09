<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\FieldTargetBase.
 */

namespace Drupal\feeds\Plugin;

use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\FeedInterface;
use Drupal\field\Plugin\Core\Entity\FieldInstance;

/**
 * Helper class for field mappers.
 */
abstract class FieldTargetBase extends TargetBase implements TargetInterface {

  /**
   * The supported field types.
   *
   * @var array
   */
  protected $fieldTypes = array();

  protected $cardinality = -1;

  protected $importer;

  protected $instance;

  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function targets() {

    $targets = array();

    $entity_type = $this->importer->processor->entityType();
    $bundle = $this->importer->processor->bundle();

    foreach (field_info_instances($entity_type, $bundle) as $name => $instance) {
      if (in_array($instance->getFieldType(), $this->fieldTypes)) {
        $targets += $this->applyTargets($instance);
      }
    }

    return $targets;
  }

  /**
   * Sets the targets for the supported field types.
   *
   * @param array $targets
   *   The targets array.
   * @param \Drupal\field\Plugin\Core\Entity\FieldInstance $instance
   *   The field instance.
   */
  abstract protected function applyTargets(FieldInstance $instance);

  /**
   * {@inheritdoc}
   */
  public function setTarget(FeedInterface $feed, EntityInterface $entity, $field_name, $value, array $mapping) {
    $value;

    $this->instance = field_info_instance($entity->entityType(), $field_name, $entity->bundle());

    $this->cardinality = $this->instance->getFieldCardinality();

    $this->entity = $entity;

    // Set a very high cardinality to make comparison simpler.
    if ($this->cardinality == -1) {
      $this->cardinality = 100000000;
    }

    $this->importer = $feed->getImporter();

    $field = $entity->get($field_name);

    $this->buildField($field, $value, $mapping);
  }

  /**
   * Bulds a field for an entity.
   *
   * @param array $field
   *   The field to populate.
   * @param array $values
   *   The values to put in the column.
   * @param array $mapping
   *   The settings for this mapping.
   *
   * @return array
   *   The newly constructed field.
   */
  protected function buildField($field, $values, array $mapping) {
    $new_values = array();

    foreach ($values as $delta => $value) {
      foreach ($value as $key => $v) {
        if (($v = $this->validate($key, $v, $mapping)) !== FALSE) {
          $new_values[$delta][$key] = $v;
          $new_values[$delta] += $this->defaults();
        }
      }
    }

    $field->setValue($new_values);
  }

  /**
   * Validates a field value.
   *
   * @param mixed $value
   *   The field value.
   *
   * @return mixed|false
   *   The value, or false if validation failed.
   */
  protected function validate($key, $value, array $mapping) {
    return (string) $value;
  }

  protected function defaults() {
    return array();
  }

}
