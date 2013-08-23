<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\FeedsPluginInterface.
 */

namespace Drupal\feeds\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface that all Feeds plugins must implement.
 */
interface FeedsPluginInterface extends PluginInspectionInterface {

  /**
   * Returns the type of plugin.
   *
   * @return string
   *   The type of plugin. Usually, one of 'fetcher', 'parser', or 'processor'.
   */
  public function pluginType();

}
