<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Target\Link.
 */

namespace Drupal\feeds\Plugin\feeds\Target;

use Drupal\Component\Annotation\Plugin;
use Drupal\feeds\Plugin\FieldTargetBase;

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

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    $values['title'] = (string) $values['title'];
    $values['url'] = (string) $values['url'];
  }

}
