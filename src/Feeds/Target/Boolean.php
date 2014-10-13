<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Target\Boolean.
 */

namespace Drupal\feeds\Feeds\Target;

use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a boolean field mapper.
 *
 * @FeedsTarget(
 *   id = "boolean",
 *   field_types = {
 *     "boolean",
 *     "list_boolean"
 *   }
 * )
 */
class Boolean extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    $values['value'] = (bool) trim((string) $values['value']);
  }

}
