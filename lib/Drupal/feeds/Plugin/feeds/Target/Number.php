<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Target\Number.
 */

namespace Drupal\feeds\Plugin\feeds\Target;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\feeds\Plugin\FieldTargetBase;
use Drupal\field\Entity\FieldInstance;

/**
 * Defines a number field mapper.
 *
 * @Plugin(
 *   id = "number",
 *   title = @Translation("Number"),
 *   field_types = {
 *     "boolean_field",
 *     "integer_field",
 *     "list_integer",
 *     "list_float",
 *     "list_boolean",
 *     "number_integer",
 *     "number_decimal",
 *     "number_float"
 *   }
 * )
 */
class Number extends FieldTargetBase {

  public function prepareValues(array &$values) {
    foreach ($values as $delta => $columns) {
      foreach ($columns as $column => $value) {
        if (is_numeric($value)) {
          $values[$delta][$column] = $value;
        }
        else {
          $values[$delta][$column] = '';
        }
      }
    }
  }

}
