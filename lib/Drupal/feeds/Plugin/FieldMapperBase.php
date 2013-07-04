<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\FieldMapperBase.
 */

namespace Drupal\feeds\Plugin;

use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedsElement;
use Drupal\field\Plugin\Core\Entity\FieldInstance;

/**
 * Helper class for field mappers.
 */
abstract class FieldMapperBase extends MapperBase {

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
  public function targets(array &$targets, $entity_type, $bundle) {
    foreach (field_info_instances($entity_type, $bundle) as $name => $instance) {
      if (in_array($instance->getFieldType(), $this->fieldTypes)) {
        $this->applyTargets($targets, $instance);
      }
    }
  }

  /**
   * Sets the targets for the supported field types.
   *
   * @param array $targets
   *   The targets array.
   * @param \Drupal\field\Plugin\Core\Entity\FieldInstance $instance
   *   The field instance.
   */
  abstract protected function applyTargets(array &$targets, FieldInstance $instance);

  /**
   * {@inheritdoc}
   */
  public function setTarget(FeedInterface $feed, EntityInterface $entity, $target, $value, array $mapping) {
    $value = (array) $value;

    list($field_name, $column) = explode(':', $target . ':');

    // We do not use drupal_strlen() here since a multibyte value still
    // returns true.
    $filtered = array_filter($value, function($var) {
      if (is_scalar($var)) {
        return strlen($var);
      }
      return TRUE;
    });

    if (!$filtered) {
      return;
    }

    // If this field only has one column, we don't have to worry about
    // alignment.
    if (!$column) {
      $value = $filtered;
    }

    $this->instance = field_info_instance($entity->entityType(), $field_name, $entity->bundle());

    $this->cardinality = $this->instance->getFieldCardinality();

    $this->entity = $entity;

    // Set a very high cardinality to make comparison simpler.
    if ($this->cardinality == -1) {
      $this->cardinality = 100000000;
    }

    $this->importer = $feed->getImporter();

    $field = isset($entity->$field_name) ? $entity->$field_name : array('und' => array());

    $entity->$field_name = $this->buildField($field, $column, $value, $mapping);
  }

  /**
   * Bulds a field for an entity.
   *
   * @param array $field
   *   The field to populate.
   * @param string $column
   *   The column of the field to populate.
   * @param array $values
   *   The values to put in the column.
   * @param array $mapping
   *   The settings for this mapping.
   *
   * @return array
   *   The newly constructed field.
   */
  protected function buildField(array $field, $column, array $values, array $mapping) {
    $delta = count($field['und']);

    foreach ($values as $value) {
      if ($delta >= $this->cardinality) {
        break;
      }

      if (is_object($value) && ($value instanceof FeedsElement)) {
        $value = $value->getValue();
      }

      if (($value = $this->validate($value)) !== FALSE) {
        $field['und'][$delta]['value'] = $value;
        $delta++;
      }
    }

    return $field;
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
  protected function validate($value) {
    return $value;
  }

}
