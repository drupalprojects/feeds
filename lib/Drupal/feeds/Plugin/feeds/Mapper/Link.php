<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Mapper\Link.
 */

namespace Drupal\feeds\Plugin\feeds\Mapper;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\FeedsElement;
use Drupal\feeds\Plugin\MapperBase;
use Drupal\feeds\Plugin\Core\Entity\Feed;

/**
 * Defines a link field mapper.
 *
 * @Plugin(
 *   id = "link",
 *   title = @Translation("Link")
 * )
 */
class Link extends MapperBase {

  /**
   * {@inheritdoc}
   */
  public function targets(array &$targets, $entity_type, $bundle) {
    foreach (field_info_instances($entity_type, $bundle) as $name => $instance) {
      $info = field_info_field($name);
      if ($info['type'] == 'link') {
        if (array_key_exists('url', $info['columns'])) {
          $targets[$name . ':url'] = array(
            'name' => t('@name: URL', array('@name' => $instance['label'])),
            'callback' => array($this, 'setTarget'),
            'description' => t('The @label field of the entity.', array('@label' => $instance['label'])),
            'real_target' => $name,
          );
        }
        if (array_key_exists('title', $info['columns'])) {
          $targets[$name . ':title'] = array(
            'name' => t('@name: Title', array('@name' => $instance['label'])),
            'callback' => array($this, 'setTarget'),
            'description' => t('The @label field of the entity.', array('@label' => $instance['label'])),
            'real_target' => $name,
          );
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  function setTarget(Feed $feed, EntityInterface $entity, $target, $value) {
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

}
