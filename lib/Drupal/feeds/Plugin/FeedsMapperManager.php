<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\FeedsMapperManager.
 */

namespace Drupal\feeds\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages feeds plugins.
 */
class FeedsMapperManager extends DefaultPluginManager {

  /**
   * Constructs a new \Drupal\feeds\Plugin\FeedsMapperManager object.
   *
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
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, LanguageManager $language_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct('feeds/Mapper', $namespaces);
    $this->alterInfo($module_handler, 'feeds_mappers');
    $this->setCacheBackend($cache_backend, $language_manager, "feeds_mappers");
  }

}
