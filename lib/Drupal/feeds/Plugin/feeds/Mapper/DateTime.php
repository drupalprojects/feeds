<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Mapper\DateTime.
 */

namespace Drupal\feeds\Plugin\feeds\Mapper;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\feeds\Plugin\FieldMapperBase;
use Drupal\field\Plugin\Core\Entity\FieldInstance;

/**
 * Defines a dateime field mapper.
 *
 * @Plugin(
 *   id = "datetime",
 *   title = @Translation("DateTime")
 * )
 */
class DateTime extends FieldMapperBase {

  /**
   * {@inheritdoc}
   */
  protected $fieldTypes = array('datetime');

  /**
   * {@inheritdoc}
   */
  protected function applyTargets(array &$targets, FieldInstance $instance) {
    $targets[$instance->getFieldName()] = array(
      'name' => $instance->label(),
      'callback' => array($this, 'setTarget'),
      'description' => t('The start date for the @name field. Also use if mapping both start and end.', array('@name' => $instance->label())),
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

      $value = new DrupalDateTime($value);

      if (!$value->hasErrors()) {
        $field['und'][$delta]['value'] = $value->format(DATETIME_DATETIME_STORAGE_FORMAT);
        $delta++;
      }
    }

    return $field;
  }

}
