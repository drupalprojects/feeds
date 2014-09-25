<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Target\Path.
 */

namespace Drupal\feeds\Feeds\Target;

use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a path field mapper.
 *
 * @Plugin(
 *   id = "path",
 *   field_types = {"field_item:path"}
 * )
 */
class Path extends FieldTargetBase {

  /**
   * {@inheritdoc}
   *
   * @todo  Support the pathauto configuration.
   */
  protected static function prepareTarget(array &$target) {
    unset($target['properties']['pid']);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    $values['alias'] = trim($values['alias']);
  }

}
