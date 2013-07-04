<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Mapper\Link.
 */

namespace Drupal\feeds\Plugin\feeds\Mapper;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\feeds\FeedsElement;
use Drupal\feeds\Plugin\FieldMapperBase;
use Drupal\field\Plugin\Core\Entity\FieldInstance;

/**
 * Defines a link field mapper.
 *
 * @Plugin(
 *   id = "link",
 *   title = @Translation("Link")
 * )
 */
class Link extends FieldMapperBase {

  /**
   * {@inheritdoc}
   */
  protected $fieldTypes = array('link');

  /**
   * {@inheritdoc}
   */
  protected function applyTargets(array &$targets, FieldInstance $instance) {

    $targets[$instance->getFieldName() . ':url'] = array(
      'name' => t('@name: URL', array('@name' => $instance->label())),
      'callback' => array($this, 'setTarget'),
      'description' => t('The @label field of the entity.', array('@label' => $instance->label())),
      'real_target' => $instance->getFieldName(),
    );
    if ($instance->getFieldSetting('title')) {
      $targets[$instance->getFieldName() . ':title'] = array(
        'name' => t('@name: Title', array('@name' => $instance->label())),
        'callback' => array($this, 'setTarget'),
        'description' => t('The @label field of the entity.', array('@label' => $instance->label())),
        'real_target' => $instance->getFieldName(),
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function buildField(array $field, $column, array $values, array $mapping) {
    $delta = 0;

    foreach ($values as $value) {
      if ($delta >= $this->cardinality) {
        break;
      }

      if (is_object($value) && ($value instanceof FeedsElement)) {
        $value = $value->getValue();
      }

      if (is_scalar($value)) {
        if (!isset($field['und'][$delta])) {
          $field['und'][$delta] = array();
        }
        $field['und'][$delta] += array('title' => '', 'url' => '');
        $field['und'][$delta][$column] = $value;
        $delta++;
      }
    }

    return $field;
  }

}
