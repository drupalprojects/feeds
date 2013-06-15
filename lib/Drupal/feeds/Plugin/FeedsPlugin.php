<?php

/**
 * @file
 * Contains Drupal\feeds\Plugin\FeedsPlugin.
 */

namespace Drupal\feeds\Plugin;

use Drupal\feeds\FeedsSource;
use Drupal\feeds\FeedsSourceInterface;
use Drupal\Component\Plugin\PluginBase;

/**
 * Implement source interface for all plugins.
 *
 * Note how this class does not attempt to store source information locally.
 * Doing this would break the model where source information is represented by
 * an object that is being passed into a Feed object and its plugins.
 */
abstract class FeedsPlugin extends PluginBase implements FeedsSourceInterface {

  // Holds the actual configuration information.
  protected $config;
  protected $importer;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->id = $configuration['importer']->id();
    $this->importer = $configuration['importer'];
    unset($configuration['importer']);
    $this->setConfig($configuration);
    $this->source_config = $this->sourceDefaults();
  }

  /**
   * Returns the type of plugin.
   *
   * @return string
   *   One of either 'fetcher', 'parser', or 'processor'.
   */
  abstract public function pluginType();

  /**
   * Save changes to the configuration of this object.
   * Delegate saving to parent (= Feed) which will collect
   * information from this object by way of getConfig() and store it.
   */
  public function save() {
    $this->importer->save();
  }

  /**
   * Returns TRUE if $this->sourceForm() returns a form.
   */
  public function hasSourceConfig() {
    $form = $this->sourceForm(array());
    return !empty($form);
  }

  /**
   * Implements FeedsSourceInterface::sourceDefaults().
   */
  public function sourceDefaults() {
    $values = array_flip(array_keys($this->sourceForm(array())));
    foreach ($values as $k => $v) {
      $values[$k] = '';
    }
    return $values;
  }

  /**
   * Callback methods, exposes source form.
   */
  public function sourceForm($source_config) {
    return array();
  }

  /**
   * Validation handler for sourceForm.
   */
  public function sourceFormValidate(&$source_config) {}

  /**
   * A source is being saved.
   */
  public function sourceSave(FeedsSource $source) {}

  /**
   * A source is being deleted.
   */
  public function sourceDelete(FeedsSource $source) {}

  /**
   * Loads on-behalf implementations from mappers/ directory.
   *
   * FeedsProcessor::map() does not load from mappers/ as only node and user
   * processor ship with on-behalf implementations.
   *
   * @see FeedsNodeProcessor::map()
   * @see FeedsUserProcessor::map()
   *
   * @todo: Use CTools Plugin API.
   */
  public static function loadMappers() {
    static $loaded = FALSE;
    if (!$loaded) {
      $path = drupal_get_path('module', 'feeds') . '/mappers';
      $files = drupal_system_listing('/.*\.inc$/', $path, 'name', 0);
      foreach ($files as $file) {
        if (strstr($file->uri, '/mappers/')) {
          require_once DRUPAL_ROOT . '/' . $file->uri;
        }
      }
    }
    $loaded = TRUE;
  }

  /**
   * Similar to setConfig but adds to existing configuration.
   *
   * @param $config
   *   Array containing configuration information. Will be filtered by the keys
   *   returned by configDefaults().
   */
  public function addConfig($config) {
    $this->config = array_merge($this->config, $config);
    $default_keys = $this->configDefaults();
    $this->config = array_intersect_key($this->config, $default_keys);
  }

  /**
   * Implements getConfig().
   *
   * Return configuration array, ensure that all default values are present.
   */
  public function getConfig() {
    return $this->config;
  }

  /**
   * Set configuration.
   *
   * @param $config
   *   Array containing configuration information. Config array will be filtered
   *   by the keys returned by configDefaults() and populated with default
   *   values that are not included in $config.
   */
  public function setConfig($config) {
    $defaults = $this->configDefaults();
    $this->config = array_intersect_key($config, $defaults) + $defaults;
  }

  /**
   * Return default configuration.
   *
   * @todo rename to getConfigDefaults().
   *
   * @return
   *   Array where keys are the variable names of the configuration elements and
   *   values are their default values.
   */
  public function configDefaults() {
    return array();
  }

  /**
   * Return configuration form for this object. The keys of the configuration
   * form must match the keys of the array returned by configDefaults().
   *
   * @return
   *   FormAPI style form definition.
   */
  public function configForm(&$form_state) {
    return array();
  }

  /**
   * Validation handler for configForm().
   *
   * Set errors with form_set_error().
   *
   * @param $values
   *   An array that contains the values entered by the user through configForm.
   */
  public function configFormValidate(&$values) {
  }

  /**
   *  Submission handler for configForm().
   *
   *  @param $values
   */
  public function configFormSubmit(&$values) {
    $this->addConfig($values);
    $this->save();
    drupal_set_message(t('Your changes have been saved.'));
    feeds_cache_clear(FALSE);
  }

}
