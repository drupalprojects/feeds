<?php

/**
 * @file
 * On behalf implementation of Feeds mapping API for file.module and
 * image.module.
 */

use Drupal\feeds\FeedsEnclosure;
use Drupal\Core\Language\Language;

/**
 * Implements hook_feeds_processor_targets_alter().
 *
 * @see FeedsNodeProcessor::getMappingTargets()
 */
function file_feeds_processor_targets_alter(&$targets, $entity_type, $bundle_name) {
  foreach (field_info_instances($entity_type, $bundle_name) as $name => $instance) {
    $info = field_info_field($name);

    if (in_array($info['type'], array('file', 'image'))) {
      $targets[$name . ':uri'] = array(
        'name' => t('@label: URI', array('@label' => $instance['label'])),
        'callback' => 'file_feeds_set_target',
        'description' => t('The URI of the @label field.', array('@label' => $instance['label'])),
        'real_target' => $name,
      );

      if ($info['type'] == 'image') {
        $targets[$name . ':alt'] = array(
          'name' => t('@label: Alt', array('@label' => $instance['label'])),
          'callback' => 'file_feeds_set_target',
          'description' => t('The alt tag of the @label field.', array('@label' => $instance['label'])),
          'real_target' => $name,
        );
        $targets[$name . ':title'] = array(
          'name' => t('@label: Title', array('@label' => $instance['label'])),
          'callback' => 'file_feeds_set_target',
          'description' => t('The title of the @label field.', array('@label' => $instance['label'])),
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
function file_feeds_set_target($feed, $entity, $target, $value) {
  if (empty($value)) {
    return;
  }

  // Make sure $value is an array of objects of type FeedsEnclosure.
  if (!is_array($value)) {
    $value = array($value);
  }

  // Add default of uri for backwards compatibility.
  list($field_name, $sub_field) = explode(':', $target . ':uri');
  $info = field_info_field($field_name);

  if ($sub_field == 'uri') {

    foreach ($value as $k => $v) {
      if (!($v instanceof FeedsEnclosure)) {
        if (is_string($v)) {
          $value[$k] = new FeedsEnclosure($v, file_get_mimetype($v));
        }
        else {
          unset($value[$k]);
        }
      }
    }
    if (empty($value)) {
      return;
    }

    static $destination;
    if (!$destination) {
      $entity_type = $feed->getImporter()->processor->entityType();
      $bundle = $feed->getImporter()->processor->bundle();

      $instance_info = field_info_instance($entity_type, $field_name, $bundle);

      // Determine file destination.
      // @todo This needs review and debugging.
      $data = array();
      if (!empty($entity->uid)) {
        $data[$entity_type] = $entity;
      }
      $destination = file_field_widget_uri($info, $instance_info, $data);
    }
  }

  // Populate entity.
  $field = isset($entity->$field_name) ? $entity->$field_name : array(Language::LANGCODE_NOT_SPECIFIED => array());
  $delta = 0;
  foreach ($value as $v) {
    if ($delta >= $info['cardinality'] && $info['cardinality'] > -1) {
      break;
    }

    if (!isset($field[Language::LANGCODE_NOT_SPECIFIED][$delta])) {
      $field[Language::LANGCODE_NOT_SPECIFIED][$delta] = array();
    }

    switch ($sub_field) {
      case 'alt':
      case 'title':
        $field[Language::LANGCODE_NOT_SPECIFIED][$delta][$sub_field] = $v;
        break;

      case 'uri':
        try {
          $file = $v->getFile($destination);
          $field[Language::LANGCODE_NOT_SPECIFIED][$delta]['entity'] = $file;
          $field[Language::LANGCODE_NOT_SPECIFIED][$delta]['fid'] = $file->id();
          // $field[Language::LANGCODE_NOT_SPECIFIED][$delta]['description'] = $file->description->value;
          // @todo: Figure out how to properly populate this field.
          $field[Language::LANGCODE_NOT_SPECIFIED][$delta]['display'] = 1;
        }
        catch (Exception $e) {
          watchdog_exception('Feeds', $e, nl2br(check_plain($e)));
        }
        break;
    }

    $delta++;
  }

  $entity->$field_name = $field;
}
