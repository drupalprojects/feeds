<?php

/**
 * @file
 * Contains \Drupal\feeds\Source\BasicFieldSource.
 */

namespace Drupal\feeds\Feeds\Source;

use Drupal\Component\Annotation\Plugin;
use Drupal\feeds\Plugin\Type\Source\FieldSourceBase;

/**
 * @Plugin(
 *   id = "basic_field",
 *   field_types = {
 *     "integer_field",
 *     "boolean_field",
 *     "number_integer",
 *     "number_decimal",
 *     "number_float",
 *     "list_integer",
 *     "list_float",
 *     "list_boolean",
 *     "datetime",
 *     "email_field",
 *     "entity_reference",
 *     "entity_reference_field"
 *   }
 * )
 */
class BasicFieldSource extends FieldSourceBase {


}
