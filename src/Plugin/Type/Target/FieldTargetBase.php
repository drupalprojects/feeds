<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\Type\Target\FieldTargetBase.
 */

namespace Drupal\feeds\Plugin\Type\Target;

use Drupal\Component\Utility\String;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\feeds\Exception\TargetValidationException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Processor\EntityProcessor;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\ImporterInterface;

/**
 * Helper class for field mappers.
 */
abstract class FieldTargetBase extends TargetBase {

  /**
   * A static cache of entity query objects stored per feed id.
   *
   * @var array
   */
  protected static $uniqueQueries;

  /**
   * The field settings.
   *
   * @var array
   */
  protected $fieldSettings;

  /**
   * {@inheritdoc}
   */
  public static function targets(array &$targets, ImporterInterface $importer, array $definition) {
    $processor = $importer->getProcessor();

    if (!$processor instanceof EntityProcessor) {
      return $targets;
    }

    $field_definitions = \Drupal::entityManager()->getFieldDefinitions($processor->entityType(), $processor->bundle());

    foreach ($field_definitions as $id => $field_definition) {
      if ($field_definition->isReadOnly() || $id === $processor->bundleKey()) {
        continue;
      }
      if (in_array($field_definition->getType(), $definition['field_types'])) {
        $target = static::prepareTarget($field_definition);
        $target->setPluginId($definition['id']);
        $targets[$id] = $target;
      }
    }
  }

  /**
   * Prepares a target definition.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return \Drupal\feeds\FieldTargetDefinition
   *   The target definition.
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    return FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('value');
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->settings = $this->targetDefinition->getFieldDefinition()->getSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function setTarget(FeedInterface $feed, EntityInterface $entity, $field_name, array $values) {
    try {
      $this->prepareValues($values);
    }
    catch (TargetValidationException $e) {
      // Validation failed.
      drupal_set_message($e->getMessage(), 'error');
      return;
    }
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

      static::$uniqueQueries[$feed->id()] = \Drupal::entityQuery($this->importer->getProcessor()->entityType())
        ->condition('feeds_item.target_id', $feed->id())
        ->range(0, 1);
    }

    return clone static::$uniqueQueries[$feed->id()];
  }

  public function getUniqueValue($feed, $target, $key, $value) {
    $base_fields = \Drupal::entityManager()->getBaseFieldDefinitions($this->importer->getProcessor()->entityType());

    if (isset($base_fields[$target])) {
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
