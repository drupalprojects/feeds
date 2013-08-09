<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Target\Number.
 */

namespace Drupal\feeds\Plugin\feeds\Target;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\feeds\Plugin\FieldTargetBase;
use Drupal\field\Plugin\Core\Entity\FieldInstance;

/**
 * Defines a number field mapper.
 *
 * @Plugin(
 *   id = "number",
 *   title = @Translation("Number")
 * )
 */
class Number extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  protected $fieldTypes = array(
    'list_integer',
    'list_float',
    'list_boolean',
    'number_integer',
    'number_decimal',
    'number_float',
  );

  /**
   * {@inheritdoc}
   */
  protected function applyTargets(FieldInstance $instance) {
    return array(
      $instance->getFieldName() => array(
        'name' => check_plain($instance->label()),
        'callback' => array($this, 'setTarget'),
        'description' => t('The @label field of the entity.', array('@label' => $instance->label())),
    ));
  }

  /**
   * {@inheritdoc}
   */
  protected function validate($key, $value, array $mapping) {
    $value = parent::validate($key, $value, $mapping);

    if (is_numeric($value)) {
      return $value;
    }

    return FALSE;
  }

}
