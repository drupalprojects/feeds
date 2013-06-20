<?php

/**
 * @file
 * On behalf implementation of Feeds mapping API for link.module.
 */

/**
 * Implements hook_feeds_processor_targets_alter().
 *
 * @see ProcessorBase::getMappingTargets()
 */
function link_feeds_processor_targets_alter(&$targets, $entity_type, $bundle_name) {
  foreach (field_info_instances($entity_type, $bundle_name) as $name => $instance) {
    $info = field_info_field($name);
    if ($info['type'] == 'link') {
      if (array_key_exists('url', $info['columns'])) {
        $targets[$name . ':url'] = array(
          'name' => t('@name: URL', array('@name' => $instance['label'])),
          'callback' => 'link_feeds_set_target',
          'description' => t('The @label field of the entity.', array('@label' => $instance['label'])),
          'real_target' => $name,
        );
      }
      if (array_key_exists('title', $info['columns'])) {
        $targets[$name . ':title'] = array(
          'name' => t('@name: Title', array('@name' => $instance['label'])),
          'callback' => 'link_feeds_set_target',
          'description' => t('The @label field of the entity.', array('@label' => $instance['label'])),
          'real_target' => $name,
        );
      }
    }
  }
}

/**
 * Callback for mapping. Here is where the actual mapping happens.
 *
 * When the callback is invoked, $target contains the name of the field the
 * user has decided to map to and $value contains the value of the feed item
 * element the user has picked as a source.
 */
function link_feeds_set_target($source, $entity, $target, $value) {
  if (empty($value)) {
    return;
  }

  // Handle non-multiple value fields.
  if (!is_array($value)) {
    $value = array($value);
  }

  // Iterate over all values.
  list($field_name, $column) = explode(':', $target);
  $info = field_info_field($field_name);

  $field = isset($entity->$field_name) ? $entity->$field_name : array('und' => array());
  $delta = 0;

  foreach ($value as $v) {
    if ($delta >= $info['cardinality'] && $info['cardinality'] > -1) {
      break;
    }

    if (is_object($v) && ($v instanceof FeedsElement)) {
      $v = $v->getValue();
    }

    if (is_scalar($v)) {
      if (!isset($field['und'][$delta])) {
        $field['und'][$delta] = array();
      }
      $field['und'][$delta] += array('title' => '', 'url' => '');
      $field['und'][$delta][$column] = $v;
      $delta++;
    }
  }

  $entity->$field_name = $field;
}
