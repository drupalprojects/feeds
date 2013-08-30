<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\TargetBase.
 */

namespace Drupal\feeds\Plugin;

use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\ConfigurablePluginBase;
use Drupal\feeds\Plugin\TargetInterface;

/**
 * @todo Document this.
 */
abstract class TargetBase extends ConfigurablePluginBase implements TargetInterface {

  /**
   * {@inheritdoc}
   */
  abstract public function targets(array &$targets);

  public function getDefaultConfiguration() {
    return array();
  }

  public function buildConfigurationForm(array $form, array &$form_state) {
    return $form;
  }

}
