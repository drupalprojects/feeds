<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\FeedsPluginManager.
 */

namespace Drupal\feeds\Plugin;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Language\Language;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;

/**
 * Manages feeds plugins.
 */
class FeedsPluginManager extends PluginManagerBase {

  /**
   * Constructs a FeedsPluginManager object.
   *
   * @param string $type
   *   The plugin type, for example fetcher.
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   */
  public function __construct($type, \Traversable $namespaces) {
    $this->discovery = new AnnotatedClassDiscovery("feeds/$type", $namespaces);
    $this->discovery = new CacheDecorator($this->discovery, "feeds_$type:" . language(Language::TYPE_INTERFACE)->langcode);
    $this->factory = new DefaultFactory($this->discovery);
  }
}
