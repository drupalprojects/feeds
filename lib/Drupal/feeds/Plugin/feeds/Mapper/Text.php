<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Mapper\Text.
 */

namespace Drupal\feeds\Plugin\feeds\Mapper;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\feeds\FeedsElement;
use Drupal\field\Plugin\Core\Entity\FieldInstance;
use Drupal\feeds\Plugin\FieldMapperBase;

/**
 * Defines a text field mapper.
 *
 * @Plugin(
 *   id = "text",
 *   title = @Translation("Text")
 * )
 */
class Text extends FieldMapperBase {

  /**
   * {@inheritdoc}
   */
  protected $fieldTypes = array(
    'list_text',
    'text',
    'text_long',
    'text_with_summary',
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

    if (isset($this->importer->processor->config['input_format'])) {
      $format = $this->importer->processor->config['input_format'];
    }

    // Allow for multiple mappings to the same target.
    $delta = count($field['und']);

    foreach ($values as $value) {

      if ($delta >= $this->cardinality) {
        break;
      }

      if (is_object($value) && ($value instanceof FeedsElement)) {
        $value = $value->getValue();
      }

      if (is_scalar($value)) {
        $field['und'][$delta]['value'] = $value;

        if (isset($format)) {
          $field['und'][$delta]['format'] = $format;
        }

        $delta++;
      }

      return $field;
    }
  }

}
