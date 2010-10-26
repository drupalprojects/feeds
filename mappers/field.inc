<?php
// $Id$

/**
 * @file
 * On behalf implementation of Feeds mapping API for field.module.
 *
 * Does actually not include mappers for field types defined in fields module
 * (because there aren't any) but mappers for all fields that contain their
 * value simply in $entity->fieldname['und'][$i]['value'].
 */

/**
 * Implements hook_feeds_processor_targets_alter().
 *
 * @see FeedsNodeProcessor::getMappingTargets().
 */
function field_feeds_processor_targets_alter(&$targets, $entity_type, $bundle_name) {
  $numeric_types = array(
    'list',
    'list_boolean',
    'list_number',
    'number_integer',
    'number_decimal',
    'number_float',
  );
  $string_types = array(
    'text',
    'text_long',
    'text_with_summary',
  );
  foreach (field_info_instances($entity_type, $bundle_name) as $name => $instance) {
    $info = field_info_field($name);
    unset($callback);
    if (in_array($info['type'], $numeric_types)) {
      $callback = 'field_feeds_set_target_numeric';
    }
    if (in_array($info['type'], $string_types)) {
      $callback = 'field_feeds_set_target';
    }
    if (isset($callback)) {
      $targets[$name] = array(
        'name' => $instance['label'],
        'callback' => $callback,
        'description' => t('The @label field of the node.', array('@label' => $instance['label'])),
      );
    }
  }
}

/**
 * Callback for mapping numerics.
 *
 * Ensure that $value is a numeric to avoid database errors.
 */
function field_feeds_set_target_numeric($entity, $target, $value) {
  if (is_numeric($value)) {
    field_feeds_set_target($entity, $target, $value);
  }
}

/**
 * Callback for mapping. Here is where the actual mapping happens.
 *
 * When the callback is invoked, $target contains the name of the field the
 * user has decided to map to and $value contains the value of the feed item
 * element the user has picked as a source.
 */
function field_feeds_set_target($entity, $target, $value) {
  // Handle non-multiple value fields.
  if (!is_array($value)) {
    $value = array($value);
  }

  $info = field_info_field($target);

  // Iterate over all values.
  $i = 0;
  $field = isset($entity->$target) ? $entity->$target : array();
  foreach ($value as $v) {
    if (!is_array($v) && !is_object($v)) {
      $field['und'][$i]['value'] = $v;
    }
    if ($info['cardinality'] == 1) {
      break;
    }
    $i++;
  }
  $entity->{$target} = $field;
}
