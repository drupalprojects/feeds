<?php

/**
 * @file
 * Contains \Drupal\feeds\TargetMapper\NumberMapper.
 */

namespace Drupal\feeds\TargetMapper;

use Drupal\feeds\Exception\ValidationException;

/**
 *
 */
class NumberMapper extends FieldTargetMapperBase {

  /**
   * {@inheritdoc}
   */
  protected function validate($key, $value) {
    $value = parent::validate($key, $value);

    if (is_numeric($value)) {
      return $value;
    }

    throw new ValidationException();
  }

}
