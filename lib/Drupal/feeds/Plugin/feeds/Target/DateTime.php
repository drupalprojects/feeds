<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Target\DateTime.
 */

namespace Drupal\feeds\Plugin\feeds\Target;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\feeds\Plugin\FieldTargetBase;
use Drupal\field\Entity\FieldInstance;

/**
 * Defines a dateime field mapper.
 *
 * @Plugin(
 *   id = "datetime",
 *   title = @Translation("DateTime"),
 *   field_types = {"datetime"}
 * )
 */
class DateTime extends FieldTargetBase {

  public function prepareValues(array &$values) {
    foreach ($values as $delta => $columns) {
      foreach ($columns as $column => $value) {
        $date = FALSE;
        if (is_numeric($value)) {
          $date = DrupalDateTime::createFromTimestamp($value);
        }
        elseif ($value instanceof \DateTime) {
          $date = DrupalDateTime::createFromDateTime($value);
        }
        elseif ($value = strtotime($value)) {
          $date = DrupalDateTime::createFromTimestamp($value);
        }

        if ($date && !$date->hasErrors()) {
          $values[$delta][$column] = $date->format(DATETIME_DATETIME_STORAGE_FORMAT);
        }
        else {
          $values[$delta][$column] = '';
        }
      }
    }
  }

}
