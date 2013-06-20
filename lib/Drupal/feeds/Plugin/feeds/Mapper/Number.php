<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Mapper\Number.
 */

namespace Drupal\feeds\Plugin\feeds\Mapper;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\FeedsElement;
use Drupal\feeds\Plugin\MapperBase;
use Drupal\feeds\Plugin\Core\Entity\Feed;

/**
 * Defines a number field mapper.
 *
 * @Plugin(
 *   id = "number",
 *   title = @Translation("Number")
 * )
 */
class Number extends MapperBase {

  /**
   * {@inheritdoc}
   */
  public function targets(array &$targets, $entity_type, $bundle) {
    $numeric_types = array(
      'list_integer',
      'list_float',
      'list_boolean',
      'number_integer',
      'number_decimal',
      'number_float',
    );
    foreach (field_info_instances($entity_type, $bundle) as $name => $instance) {
      $info = field_info_field($name);

      if (in_array($info['type'], $numeric_types)) {
        $targets[$name] = array(
          'name' => check_plain($instance['label']),
          'callback' => array($this, 'setTarget'),
          'description' => t('The @label field of the entity.', array('@label' => $instance['label'])),
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  function setTarget(Feed $feed, EntityInterface $entity, $target, $value) {
    // Do not perform the regular empty() check here. 0 is a valid value. That's
    // really just a performance thing anyway.

    if (!is_array($value)) {
      $value = array($value);
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

      if (is_numeric($v)) {
        $field['und'][$delta]['value'] = $v;
        $delta++;
      }
    }

    $entity->$target = $field;
  }

}
