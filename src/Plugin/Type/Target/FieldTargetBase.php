<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\Type\Target\FieldTargetBase.
 */

namespace Drupal\feeds\Plugin\Type\Target;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Processor\EntityProcessor;
use Drupal\feeds\ImporterInterface;
use Drupal\field\Entity\FieldInstanceConfig;

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
   * A static cache of entity query objects stored per feed id.
   *
   * @var array
   */
  protected static $uniqueQueries;

  /**
   * {@inheritdoc}
   */
  public static function targets(array &$targets, ImporterInterface $importer, array $definition) {
    if ($importer->getProcessor() instanceof EntityProcessor) {
      if (static::$properties === NULL) {
        static::buildProperties($importer->getProcessor());
      }

      foreach (static::$properties as $id => $property) {
        if (!empty($property['type']) && in_array($property['type'], $definition['field_types'])) {
          static::prepareTarget($property);
          $targets[$id] = $property;
          $targets[$id]['id'] = $definition['id'];
        }
      }
    }
  }

  /**
   * Discovers all fields/properties of an entity.
   *
   * @param \Drupal\feeds\ImporterInterface $importer
   *   The importer.
   */
  protected static function buildProperties(EntityProcessor $processor) {
    $entity_type = $processor->entityType();

    $info = \Drupal::entityManager()->getDefinition($entity_type);
    $bundle_key = NULL;
    if ($info->getBundleEntityType()) {
      $bundle_key = $info->getBundleEntityType();
    }

    $field_definitions = \Drupal::entityManager()->getFieldDefinitions($entity_type, $processor->bundle());

    foreach ($field_definitions as $id => $field_definition) {
      if ($field_definition->isReadOnly() || $id == $bundle_key) {
        continue;
      }

      static::$properties[$id] = $field_definition->getItemDefinition()->toArray();
      static::$properties[$id]['label'] = $field_definition->getLabel();
      static::$properties[$id]['description'] = $field_definition->getDescription();
      static::$properties[$id]['settings'] = $field_definition->getSettings();

      foreach ($field_definition->getItemDefinition()->getPropertyDefinitions() as $property => $data_definition) {
        if (!$data_definition->isComputed()) {
          static::$properties[$id]['properties'][$property] = $data_definition->toArray();
        }
      }

      $instance = FieldInstanceConfig::loadByName($entity_type, $processor->bundle(), $id);

      if ($instance) {
        static::$properties[$id]['label'] = $instance->getLabel();
        static::$properties[$id]['description'] = $instance->getDescription();
        static::$properties[$id]['settings'] = $instance->getSettings();
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
   * {@inheritdoc}
   */
  public function setTarget(FeedInterface $feed, $entity, $field_name, array $values) {
    $this->prepareValues($values);
    $entity->get($field_name)->setValue($values);
  }

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

  protected function getUniqueQuery(FeedInterface $feed) {
    if (!isset(static::$uniqueQueries[$feed->id()])) {
      $entity_type = $feed->getImporter()->getProcessor()->entityType();

      static::$uniqueQueries[$feed->id()] = \Drupal::entityQuery($entity_type)
        ->condition('feeds_item.target_id', $feed->id())
        ->range(0, 1);
    }

    return clone static::$uniqueQueries[$feed->id()];
  }

  public function getUniqueValue($feed, $target, $key, $value) {

    if (empty($this->settings['configurable'])) {
      $field = $target;
    }
    else {
      $field = "$target.$key";
    }
    if ($result = $this->getUniqueQuery($feed)->condition($field, $value)->execute()) {
      return reset($result);
    }
  }

}
