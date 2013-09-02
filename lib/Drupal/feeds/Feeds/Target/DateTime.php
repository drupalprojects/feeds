<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Target\DateTime.
 */

namespace Drupal\feeds\Feeds\Target;

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
   * The datetime storage format.
   *
   * @var string
   */
  protected $storageFormat;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $settings, $plugin_id, array $plugin_definition) {
    parent::__construct($settings, $plugin_id, $plugin_definition);
    $datetime_type = $this->settings['instance']->getFieldSetting('datetime_type');
    $this->storageFormat = $datetime_type == 'date' ? DATETIME_DATE_STORAGE_FORMAT : DATETIME_DATETIME_STORAGE_FORMAT;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    $date = FALSE;
    $value = trim($values['value']);
    if (is_numeric($value) || is_string($value) && $value = strtotime($value)) {
      $date = DrupalDateTime::createFromTimestamp($value, DATETIME_STORAGE_TIMEZONE);
    }
    elseif ($value instanceof \DateTime) {
      $date = DrupalDateTime::createFromDateTime($value);
    }

    if ($date && !$date->hasErrors()) {
      $values['value'] = $date->format($this->storageFormat);
    }
    else {
      $values['value'] = '';
    }
  }

}
