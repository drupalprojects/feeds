<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Target\DateTime.
 */

namespace Drupal\feeds\Plugin\feeds\Target;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\feeds\Plugin\FieldTargetBase;

/**
 * Defines a dateime field mapper.
 *
 * @Plugin(
 *   id = "datetime",
 *   field_types = {"datetime"}
 * )
 */
class DateTime extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    $date = FALSE;
    $value = $values['value'];

    if (is_numeric($value)) {
      $date = DrupalDateTime::createFromTimestamp($value);
    }
    elseif ($value instanceof \DateTime) {
      $date = DrupalDateTime::createFromDateTime($value);
    }
    elseif (is_string($value) && $value = strtotime($value)) {
      $date = DrupalDateTime::createFromTimestamp($value);
    }

    if ($date && !$date->hasErrors()) {
      $values['value'] = $date->format(DATETIME_DATETIME_STORAGE_FORMAT);
    }
    else {
      $values['value'] = '';
    }
  }

}
