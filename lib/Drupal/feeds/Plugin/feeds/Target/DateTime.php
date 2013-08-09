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
use Drupal\field\Plugin\Core\Entity\FieldInstance;

/**
 * Defines a dateime field mapper.
 *
 * @Plugin(
 *   id = "datetime",
 *   title = @Translation("DateTime")
 * )
 */
class DateTime extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  protected $fieldTypes = array('datetime');

  /**
   * {@inheritdoc}
   */
  protected function applyTargets(FieldInstance $instance) {
    return array(
      $instance->getFieldName() => array(
        'name' => $instance->label(),
        'callback' => array($this, 'setTarget'),
        'description' => t('The start date for the @name field. Also use if mapping both start and end.', array('@name' => $instance->label())),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function validate($key, $value, array $mapping) {
    $value = parent::validate($key, $value, $mapping);

    $value = new DrupalDateTime($value);

    if (!$value->hasErrors()) {
      return $value->format(DATETIME_DATETIME_STORAGE_FORMAT);
    }

    return FALSE;
  }

}
