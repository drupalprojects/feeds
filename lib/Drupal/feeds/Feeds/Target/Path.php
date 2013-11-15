<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Target\Path.
 */

namespace Drupal\feeds\Feeds\Target;

use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a path field mapper.
 *
 * @Plugin(
 *   id = "path",
 *   field_types = {"field_item:path"}
 * )
 */
class Path extends FieldTargetBase {

  /**
   * {@inheritdoc}
   *
   * @todo  Support the pathauto configuration.
   */
  protected static function prepareTarget(array &$target) {
    unset($target['properties']['pid']);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array('pathauto_override' => FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state, array $target = array()) {
    if (module_exists('path_auto')) {
      $form['pathauto_override'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Allow Pathauto to set the alias if the value is empty.'),
        '#default_value' => $this->getConfiguration('pathauto_override'),
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (!module_exists('pathauto')) {
      return;
    }

    if (!$this->getConfiguration('pathauto_override')) {
      return $this->t('Do not allow Pathauto if empty.');
    }
    else {
      return $this->t('Allow Pathauto if empty.');
    }
  }

}
