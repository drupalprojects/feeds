<?php

/**
 * @file
 * Contains \Drupal\feeds\TargetMapper\FieldTargetMapperBase.
 */

namespace Drupal\feeds\TargetMapper;

use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Entity\FieldInstance;

abstract class FieldTargetMapperBase extends TargetMapperBase {

  protected $importer;

  protected $instance;

  protected $entity;

  protected $initialValues = array();

  /**
   * Constructs a FieldTargetMapperBase object.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed being imported.
   * @param array $configuration
   *   A configuration array.
   */
  public function __construct(FeedInterface $feed, array $configuration) {
    parent::__construct($feed, $configuration);
    $processor = $this->importer->getProcessor();
    $this->instance = field_info_instance($processor->entityType(), $configuration['field_name'], $processor->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function setTarget(EntityInterface $entity, $field_name, array $values) {
    $field = $entity->get($field_name);

    // Initialize the new values. This should be an empty array or columns.
    foreach ($values as $delta => $value) {
      $item = $field->offsetGet($delta);

      foreach ($value as $column => $column_value) {
        $item->set($column, $value);
      }
    }
  }

  /**
   * Validates a field column value.
   *
   * @param string $column
   *   The column in the field we are validating.
   * @param mixed $column_value
   *   The field column value.
   *
   * @return scalar
   *   The validated value.
   *
   * @throws \Drupal\feeds\Exception\ValidationException
   *   Thrown when the column value does not validate.
   */
  protected function validate(EntityInterface $entity, $column, $column_value) {
    return (string) $column_value;
  }

}
