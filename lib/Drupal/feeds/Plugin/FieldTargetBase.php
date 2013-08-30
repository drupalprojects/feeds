<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\FieldTargetBase.
 */

namespace Drupal\feeds\Plugin;

use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\FeedInterface;
use Drupal\field\Entity\FieldInstance;
use Drupal\field\Field;

/**
 * Helper class for field mappers.
 *
 * @todo
 */
abstract class FieldTargetBase extends TargetBase implements TargetInterface {

  /**
   * {@inheritdoc}
   */
  public function targets(array &$targets) {

    foreach ($targets as &$target) {
      if (!empty($target['type']) && in_array($target['type'], $this->pluginDefinition['field_types'])) {
        $this->prepareTarget($target);
        $target['target'] = $this->getPluginId();
      }
    }
  }

  protected function prepareTarget(array &$target) {}

  public function prepareValues(array &$values) {
    foreach ($values as $delta => $columns) {
      foreach ($columns as $column => $value) {
        $values[$delta][$column] = (string) $value;
      }
    }
  }

}
