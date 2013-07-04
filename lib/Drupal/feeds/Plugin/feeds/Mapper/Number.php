<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Mapper\Number.
 */

namespace Drupal\feeds\Plugin\feeds\Mapper;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\feeds\FeedsElement;
use Drupal\feeds\Plugin\FieldMapperBase;
use Drupal\field\Plugin\Core\Entity\FieldInstance;

/**
 * Defines a number field mapper.
 *
 * @Plugin(
 *   id = "number",
 *   title = @Translation("Number")
 * )
 */
class Number extends FieldMapperBase {

  /**
   * {@inheritdoc}
   */
  protected $fieldTypes = array(
    'list_integer',
    'list_float',
    'list_boolean',
    'number_integer',
    'number_decimal',
    'number_float',
  );

  /**
   * {@inheritdoc}
   */
  protected function applyTargets(array &$targets, FieldInstance $instance) {
    $targets[$instance->getFieldName()] = array(
      'name' => check_plain($instance->label()),
      'callback' => array($this, 'setTarget'),
      'description' => t('The @label field of the entity.', array('@label' => $instance->label())),
    );
  }

  /**
   * {@inheritdoc}
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

      if (is_numeric($value)) {
        $field['und'][$delta]['value'] = $value;
        $delta++;
      }
    }

    return $field;
  }

}
