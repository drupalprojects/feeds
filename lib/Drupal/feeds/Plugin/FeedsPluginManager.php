<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\FeedsPluginManager.
 */

namespace Drupal\feeds\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\feeds\Plugin\PluginBase;

/**
 * Manages feeds plugins.
 */
class FeedsPluginManager extends DefaultPluginManager {

  /**
   * The plugin type this is managing.
   *
   * @var string
   */
  protected $pluginType;

  /**
   * Constructs a new \Drupal\feeds\Plugin\FeedsPluginManager object.
   *
   * @param string $type
   *   The plugin type. Either fetcher, parser, or processor.
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct($type, \Traversable $namespaces, CacheBackendInterface $cache_backend, LanguageManager $language_manager, ModuleHandlerInterface $module_handler) {
    $this->pluginType = $type;
    parent::__construct('Plugin/feeds/' . ucfirst($type), $namespaces);
    $this->alterInfo($module_handler, "feeds_{$type}_plugins");
    $this->setCacheBackend($cache_backend, $language_manager, "feeds_{$type}_plugins");
  }

}
