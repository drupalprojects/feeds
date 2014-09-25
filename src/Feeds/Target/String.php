<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Target\String.
 */

namespace Drupal\feeds\Feeds\Target;

use Drupal\Component\Utility\Unicode;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a string field mapper.
 *
 * @Plugin(
 *   id = "string",
 *   field_types = {"field_item:string"}
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
    // Trim the value if it's too long.
    if (!empty($this->settings['settings']['max_length'])) {
      $values['value'] = Unicode::substr($values['value'], 0, $this->settings['settings']['max_length']);
    }
  }

}
