<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\PluginBag.
 */

namespace Drupal\feeds\Plugin;

use Drupal\Component\Plugin\DefaultSinglePluginBag;

/**
 * Provides a container for lazily loading Feeds plugins.
 */
class PluginBag extends DefaultSinglePluginBag {

  /**
   * {@inheritdoc}
   */
  public function addInstanceID($id) {
    $this->configuration = array();
    parent::addInstanceID($id);
  }

}
