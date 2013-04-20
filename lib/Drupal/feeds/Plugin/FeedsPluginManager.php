<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\FeedsPluginManager.
 */

namespace Drupal\feeds\Plugin;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\Core\Plugin\Factory\ContainerFactory;

/**
 * Manages feeds plugins.
 */
class FeedsPluginManager extends PluginManagerBase {

  /**
   * Constructs a FeedsPluginManager object.
   *
   * @param string $type
   *   The plugin type, for example fetcher.
   * @param array $namespaces
   *   An array of paths keyed by it's corresponding namespaces.
   */
  public function __construct($type, array $namespaces) {
    $this->discovery = new AnnotatedClassDiscovery('feeds', $type, $namespaces);
    $this->discovery = new CacheDecorator($this->discovery, "feeds_$type:" . language(LANGUAGE_TYPE_INTERFACE)->langcode);
    $this->factory = new ContainerFactory($this);
  }
}
