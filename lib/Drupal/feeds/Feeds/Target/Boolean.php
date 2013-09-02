<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Target\Boolean.
 */

namespace Drupal\feeds\Feeds\Target;

use Drupal\Component\Annotation\Plugin;
use Drupal\feeds\Plugin\FieldTargetBase;

/**
 * Defines a boolean field mapper.
 *
 * @Plugin(
 *   id = "boolean",
 *   field_types = {
 *     "boolean_field",
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
