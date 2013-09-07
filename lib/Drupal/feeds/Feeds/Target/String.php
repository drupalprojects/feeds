<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Target\String.
 */

namespace Drupal\feeds\Feeds\Target;

use Drupal\Component\Annotation\Plugin;
use Drupal\Component\Utility\Unicode;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a string field mapper.
 *
 * @Plugin(
 *   id = "string",
 *   field_types = {"string_field"}
 * )
 */
class String extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(array &$target) {
    $target['unique']['value'] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    $values['value'] = (string) $values['value'];

    // @todo We need to generalize this big time. We might be able to get rid of
    // some target classes if property_constraints get used across the board.
    if (!empty($this->settings['property_constraints'])) {
      foreach ($this->settings['property_constraints'] as $key => $constraint) {
        foreach ($constraint as $name => $condition) {
          switch ($name) {
            case 'Length':
              $values[$key] = Unicode::substr($values[$key], 0, $condition['max']);
              break;
          }
        }
      }
    }
  }

}
