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
use Drupal\feeds\Plugin\FeedsPlugin;

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
   * Contains instantiated controllers keyed by controller type and plguin id.
   *
   * @var array
   */
  protected $controllers = array();

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
    parent::__construct('feeds/' . ucfirst($type), $namespaces);
    $this->alterInfo($module_handler, 'feeds_plugin');
    $this->setCacheBackend($cache_backend, $language_manager, "feeds_{$type}_plugins");
  }

  /**
   * Checks whether a certain plugin has a certain controller.
   *
   * @param string $plugin_id
   *   The name of the plugin type.
   * @param string $controller_type
   *   The name of the controller.
   *
   * @return bool
   *   Returns TRUE if the plugin type has the controller, else FALSE.
   */
  public function hasController($plugin_id, $controller_type) {
    $definition = $this->getDefinition($plugin_id);
    return !empty($definition['controllers'][$controller_type]);
  }

  /**
   * Returns an plugin controller class.
   *
   * @param string $plugin_id
   *   The name of the plugin type
   * @param string $controller_type
   *   The name of the controller.
   * @param string|null $nested
   *   (optional) If this controller definition is nested, the name of the key.
   *   Defaults to NULL.
   *
   * @return string
   *   The class name for this controller instance.
   */
  public function getControllerClass($plugin_id, $controller_type, $nested = NULL) {
    $definition = $this->getDefinition($plugin_id);
    $definition = $definition['controllers'];
    if (empty($definition[$controller_type])) {
      throw new \InvalidArgumentException(sprintf('The plugin (%s) did not specify a %s.', $plugin_id, $controller_type));
    }

    $class = $definition[$controller_type];

    // Some class definitions can be nested.
    if (isset($nested)) {
      if (empty($class[$nested])) {
        throw new \InvalidArgumentException(sprintf("Missing '%s: %s' for plugin '%s'", $controller_type, $nested, $plugin_id));
      }

      $class = $class[$nested];
    }

    if (!class_exists($class)) {
      throw new \InvalidArgumentException(sprintf('Plugin (%s) %s "%s" does not exist.', $plugin_id, $controller_type, $class));
    }

    return $class;
  }


  /**
   * Creates a new form controller instance.
   *
   * @param string $plugin_type
   *   The plugin type for this form controller.
   * @param string $operation
   *   The name of the operation to use, e.g., 'default'.
   *
   * @return \Drupal\Core\Entity\EntityFormControllerInterface
   *   A form controller instance.
   */
  public function getFormController($plugin_id, $operation) {
    if (!isset($this->controllers['form'][$operation][$plugin_id])) {
      $class = $this->getControllerClass($plugin_id, 'form', $operation);
      $this->controllers['form'][$operation][$plugin_id] = new $class($operation);
    }
    return $this->controllers['form'][$operation][$plugin_id];
  }

/**
 * Returns the default form state for the given plugin and operation.
 *
 * @param EntityInterface $plugin
 *   The plugin to be created or edited.
 * @param $operation
 *   (optional) The operation identifying the form to be processed.
 *
 * @return
 *   A $form_state array already filled the plugin form controller.
 */
protected function formStateDefaults(FeedsPlugin $plugin, $operation = 'default') {
  $form_state = array();
  $controller = $this->getFormController($plugin->getPluginID(), $operation);
  $controller->setPlugin($plugin);
  $form_state['build_info']['callback_object'] = $controller;
  $form_state['build_info']['base_form_id'] = $controller->getBaseFormID();
  $form_state['build_info']['args'] = array();
  return $form_state;
}

  /**
   * Returns the built and processed form for the given plugin.
   *
   * @param \Drupal\Core\Entity\EntityInterface $plugin
   *   The plugin to be created or edited.
   * @param string $operation
   *   (optional) The operation identifying the form variation to be returned.
   *   Defaults to 'default'.
   * @param array $form_state
   *   (optional) An associative array containing the current state of the form.
   *   Use this to pass additional information to the form, such as the
   *   langcode. Defaults to an empty array.
   * @code
   *   $form_state['langcode'] = $langcode;
   *   $manager = Drupal::pluginManager();
   *   $form = $manager->getForm($plugin, 'default', $form_state);
   * @endcode
   *
   * @return array
   *   The processed form for the given plugin and operation.
   */
  public function getForm(FeedsPlugin $plugin, $operation = 'default', array $form_state = array()) {
    $form_state += $this->formStateDefaults($plugin, $operation);
    $form_id = $form_state['build_info']['callback_object']->getFormID();
    return drupal_build_form($form_id, $form_state);
  }

}
