<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\Type\FeedsPluginManager.
 */

namespace Drupal\feeds\Plugin\Type;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages Feeds plugins.
 */
class FeedsPluginManager extends DefaultPluginManager {

  /**
   * The plugin being managed.
   *
   * @var string
   */
  protected $pluginType;

  /**
   * Constructs a new \Drupal\feeds\Plugin\Type\FeedsPluginManager object.
   *
   * @param string $type
   *   The plugin type. Either fetcher, parser, or processor, handler, source,
   *   target, or other.
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

    // Get us some proper namespaces.
    parent::__construct('Feeds/' . ucfirst($type), $namespaces, $module_handler);
    $this->alterInfo("feeds_{$type}_plugins");
    $this->setCacheBackend($cache_backend, "feeds_{$type}_plugins");
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    // Add plugin_type key so that we can determie the plugin type later.
    $definition['plugin_type'] = $this->pluginType;
  }

}
