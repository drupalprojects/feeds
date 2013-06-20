<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Mapper\DateTime.
 */

namespace Drupal\feeds\Plugin\feeds\Mapper;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\Plugin\MapperBase;
use Drupal\feeds\Plugin\Core\Entity\Feed;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Defines a dateime field mapper.
 *
 * @Plugin(
 *   id = "datetime",
 *   title = @Translation("DateTime")
 * )
 */
class DateTime extends MapperBase {

  /**
   * {@inheritdoc}
   */
  public function targets(array &$targets, $entity_type, $bundle) {
    foreach (field_info_instances($entity_type, $bundle) as $name => $instance) {
      $info = field_info_field($name);
      if ($info['type'] === 'datetime') {
        $targets[$name] = array(
          'name' => t('@name: Start', array('@name' => $instance['label'])),
          'callback' => array($this, 'setTarget'),
          'description' => t('The start date for the @name field. Also use if mapping both start and end.', array('@name' => $instance['label'])),
          'real_target' => $name,
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  function setTarget(Feed $feed, EntityInterface $entity, $target, $value) {
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

}



