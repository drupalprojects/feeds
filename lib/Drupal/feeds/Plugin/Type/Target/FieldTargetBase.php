<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\Type\Target\FieldTargetBase.
 */

namespace Drupal\feeds\Plugin\Type\Target;

use Drupal\feeds\ImporterInterface;

/**
 * Helper class for field mappers.
 *
 * @todo
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


  protected static function buildProperties(ImporterInterface $importer) {
    $processor = $importer->getProcessor();
    $entity = entity_create($processor->entityType(), $processor->getConfiguration('values'));

    $info = entity_get_info($processor->entityType());
    $bundle_key = NULL;
    if (isset($info['entity_keys']['bundle'])) {
      $bundle_key = $info['entity_keys']['bundle'];
    }

    foreach ($entity as $id => $field) {
      $definition = $field->getItemDefinition();

      if (!empty($definition['read-only']) || $id == $bundle_key) {
        continue;
      }

      static::$properties[$id] = $definition;
      $field_properties = $field->getPropertyDefinitions();
      static::$properties[$id]['properties'] = array_filter($field_properties, function($property) {
        return empty($property['computed']);
      });

      if ($instance = $field->getFieldDefinition()) {
        static::$properties[$id]['label'] = $instance->getFieldLabel();
        static::$properties[$id]['description'] = $instance->getFieldDescription();
        static::$properties[$id]['type'] = $instance->getFieldType();
        static::$properties[$id]['instance'] = $instance;
      }
    }
  }

  protected static function prepareTarget(array &$target) {}

  public function prepareValues(array &$values) {
    foreach ($values as $delta => &$columns) {
      $this->prepareValue($delta, $columns);
    }
  }

  protected function prepareValue($delta, array &$values) {
    foreach ($values as $column => $value) {
      $values[$delta][$column] = (string) $value;
    }
  }

}
