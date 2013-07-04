<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\FieldMapperBase.
 */

namespace Drupal\feeds\Plugin;

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

}
