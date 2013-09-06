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
    unset($target['properties']['target_id']);
    unset($target['properties']['revision_id']);
    unset($target['properties']['imported']);
    unset($target['properties']['hash']);
    unset($target['properties']['entity']);
    unset($target['properties']['label']);
    unset($target['properties']['access']);
    $target['unique']['url'] = TRUE;
    $target['unique']['guid'] = TRUE;
  }

}
