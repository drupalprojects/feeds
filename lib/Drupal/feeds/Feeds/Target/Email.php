<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Target\Email.
 */

namespace Drupal\feeds\Feeds\Target;

use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a email field mapper.
 *
 * @Plugin(
 *   id = "email",
 *   field_types = {"email_field"}
 * )
 */
class Email extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    $values['value'] = trim($values['value']);
    if (!filter_var($values['value'], FILTER_VALIDATE_EMAIL)) {
      $values['value'] = '';
    }
  }

}
