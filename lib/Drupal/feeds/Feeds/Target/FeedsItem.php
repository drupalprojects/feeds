<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Target\FeedsItem.
 */

namespace Drupal\feeds\Feeds\Target;

use Drupal\Component\Annotation\Plugin;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a feeds_item field mapper.
 *
 * @Plugin(
 *   id = "feeds_item",
 *   field_types = {"feeds_item"}
 * )
 */
class FeedsItem extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(array &$target) {
    unset($target['properties']['fid']);
    unset($target['properties']['imported']);
    unset($target['properties']['hash']);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    // $values['title'] = (string) $values['title'];
    // $values['url'] = (string) $values['url'];
  }

}
