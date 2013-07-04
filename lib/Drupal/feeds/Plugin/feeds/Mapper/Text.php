<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Mapper\Text.
 */

namespace Drupal\feeds\Plugin\feeds\Mapper;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\FeedsElement;
use Drupal\feeds\Plugin\Core\Entity\Feed;
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
  function setTarget(Feed $feed, EntityInterface $entity, $target, $value) {
    if (empty($value)) {
      return;
    }

    if (!is_array($value)) {
      $value = array($value);
    }

    if (isset($feed->importer->processor->config['input_format'])) {
      $format = $feed->importer->processor->config['input_format'];
    }

    $info = field_info_field($target);

    // Iterate over all values.
    $field = isset($entity->$target) ? $entity->$target : array('und' => array());

    // Allow for multiple mappings to the same target.
    $delta = count($field['und']);

    foreach ($value as $v) {

      if ($info['cardinality'] == $delta) {
        break;
      }

      if (is_object($v) && ($v instanceof FeedsElement)) {
        $v = $v->getValue();
      }

      if (is_scalar($v)) {
        $field['und'][$delta]['value'] = $v;

        if (isset($format)) {
          $field['und'][$delta]['format'] = $format;
        }

        $delta++;
      }
    }

    $entity->$target = $field;
  }

}
