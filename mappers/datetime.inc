<?php

/**
 * @file
 * On behalf implementation of Feeds mapping API for date
 */

use Drupal\feeds\FeedsDateTimeElement;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Implements hook_feeds_processor_targets_alter().
 *
 * @see FeedsNodeProcessor::getMappingTargets().
 *
 * @todo Only provides "end date" target if field allows it.
 */
function datetime_feeds_processor_targets_alter(&$targets, $entity_type, $bundle) {
  foreach (field_info_instances($entity_type, $bundle) as $name => $instance) {
    $info = field_info_field($name);
    if ($info['type'] === 'datetime') {
      $targets[$name] = array(
        'name' => t('@name: Start', array('@name' => $instance['label'])),
        'callback' => 'datetime_feeds_set_target',
        'description' => t('The start date for the @name field. Also use if mapping both start and end.', array('@name' => $instance['label'])),
        'real_target' => $name,
      );
    }
  }
}

/**
 * Implements hook_feeds_set_target().
 *
 * @param $node
 *   The target node.
 * @param $field_name
 *   The name of field on the target node to map to.
 * @param $value
 *   The value to be mapped. Should be either a (flexible) date string
 *   or a FeedsDateTimeElement object.
 *
 */
function datetime_feeds_set_target($source, $entity, $target, $value) {
  if (!is_array($value)) {
    $value = array($value);
  }

  $info = field_info_field($target);
  $field = isset($entity->$target) ? $entity->$target : array('und' => array());
  $delta = count($field['und']);

  foreach ($value as $v) {
    if ($delta >= $info['cardinality'] && $info['cardinality'] > -1) {
      break;
    }

    $v = new DrupalDateTime($v);

    if (!$v->hasErrors()) {
      $field['und'][$delta]['value'] = $v->format(DATETIME_DATETIME_STORAGE_FORMAT);
      $delta++;
    }
  }

  $entity->$target = $field;
}
