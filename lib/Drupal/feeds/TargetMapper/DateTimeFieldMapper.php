<?php

/**
 * @file
 * Contains \Drupal\feeds\TargetMapper\DateTimeFieldMapper.
 */

namespace Drupal\feeds\TargetMapper;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\feeds\Exception\ValidationException;

/**
 *
 */
class DateTimeFieldMapper extends FieldTargetMapperBase {

  /**
   * {@inheritdoc}
   */
  protected function validate($key, $value) {
    $value = parent::validate($key, $value);

    $value = new DrupalDateTime($value);

    if (!$value->hasErrors()) {
      return $value->format(DATETIME_DATETIME_STORAGE_FORMAT);
    }

    throw new ValidationException();
  }

}
