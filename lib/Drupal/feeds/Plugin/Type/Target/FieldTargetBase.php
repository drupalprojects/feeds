<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\Type\Target\FieldTargetBase.
 */

namespace Drupal\feeds\Plugin\Type\Target;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\ImporterInterface;

/**
 * Helper class for field mappers.
 */
abstract class FieldTargetBase extends TargetBase implements TargetInterface {

  /**
   * A static cache of entity properties.
   *
   * @var array
   */
  protected static $properties;

  /**
   * {@inheritdoc}
   */
  public static function targets(array &$targets, ImporterInterface $importer, array $definition) {
    if (static::$properties === NULL) {
      static::buildProperties($importer);
    }

    foreach (static::$properties as $id => $property) {
      if (!empty($property['type']) && in_array($property['type'], $definition['field_types'])) {
        static::prepareTarget($property);
        $targets[$id] = $property;
        $targets[$id]['id'] = $definition['id'];
      }
    }
  }

  /**
   * Discovers all fields/properties of an entity.
   *
   * @param \Drupal\feeds\ImporterInterface $importer
   *   The importer.
   */
  protected static function buildProperties(ImporterInterface $importer) {
    $processor = $importer->getProcessor();

    $info = \Drupal::entityManager()->getDefinition($processor->entityType());
    $bundle_key = NULL;
    if (isset($info['entity_keys']['bundle'])) {
      $bundle_key = $info['entity_keys']['bundle'];
    }

    $field_definitions = \Drupal::entityManager()->getFieldDefinitions($processor->entityType(), $processor->bundle());

    foreach ($field_definitions as $id => $definition) {
      if (!empty($definition['read-only']) || $id == $bundle_key) {
        continue;
      }
      $field = \Drupal::typedData()->createInstance($definition['type'], $definition);

      static::$properties[$id] = $definition;
      static::$properties[$id]['properties'] = $field->getPropertyDefinitions();

      // static::$properties[$id]['properties'] = array_filter($field_properties, function($property) {
      //   return empty($property['computed']);
      // });
      $instance_id = $processor->entityType() . '.' . $processor->bundle() . '.' . $id;
      $instance = \Drupal::entityManager()->getStorageController('field_instance')->load($instance_id);

      if ($instance) {
        static::$properties[$id]['label'] = $instance->getFieldLabel();
        static::$properties[$id]['description'] = $instance->getFieldDescription();
        static::$properties[$id]['type'] = $instance->getFieldType();
        static::$properties[$id]['settings'] = $instance->getFieldSettings();
      }
    }
  }

  /**
   * Prepares the automatically generated target array.
   *
   * Subclasses of FieldTargetBase should use this to massage the target info.
   *
   * @param array $target
   *   The target info array.
   */
  protected static function prepareTarget(array &$target) {}

  /**
   * Prepares the the values that will be mapped to an entity.
   *
   * @param array $values
   *   The values.
   */
  protected function prepareValues(array &$values) {
    foreach ($values as $delta => &$columns) {
      $this->prepareValue($delta, $columns);
    }
  }

  /**
   * Prepares a single value.
   *
   * @param int $delta
   *   The field delta.
   * @param array $values
   *   The values.
   */
  protected function prepareValue($delta, array &$values) {
    foreach ($values as $column => $value) {
      $values[$column] = (string) $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setTarget(FeedInterface $feed, $entity, $field_name, array $values) {
    $this->prepareValues($values);
    $entity->get($field_name)->setValue($values);
  }

}
