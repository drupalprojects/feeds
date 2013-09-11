<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Target\Link.
 */

namespace Drupal\feeds\Feeds\Target;

use Drupal\Component\Annotation\Plugin;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a link field mapper.
 *
 * @Plugin(
 *   id = "link",
 *   field_types = {"link"}
 * )
 */
class Link extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(array &$target) {
    unset($target['properties']['attributes']);
  }

}
