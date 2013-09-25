<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Target\Number.
 */

namespace Drupal\feeds\Feeds\Target;

use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a number field mapper.
 *
 * @Plugin(
 *   id = "number",
 *   field_types = {
 *     "integer_field",
 *     "list_integer",
 *     "list_float",
 *     "number_integer",
 *     "number_decimal",
 *     "number_float"
 *   }
 * )
 */
class Number extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    $values['value'] = trim($values['value']);

    if (!is_numeric($values['value'])) {
      $values['value'] = '';
    }
  }

}
