<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\PluginBase.
 */

namespace Drupal\feeds\Plugin;

use Drupal\feeds\FeedInterface;
use Drupal\Component\Plugin\PluginBase as DrupalPluginBase;

/**
 * Implement source interface for all plugins.
 *
 * Note how this class does not attempt to store source information locally.
 * Doing this would break the model where source information is represented by
 * an object that is being passed into a FeedInterface object and its plugins.
 */
abstract class PluginBase extends DrupalPluginBase {

  // Holds the actual configuration information.
  protected $config;
  protected $importer;

  /**
   * Constructs a PluginBase object.
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
   * Implements FeedInterface::sourceDefaults().
   */
  public function sourceDefaults() {
    return array();
  }

  /**
   * Stub for plugins implementing FeedPluginFormInterface.
   *
   * @see \Drupal\feeds\FeedPluginFormInterface
   */
  public function feedFormValidate(array $form, array &$form_state, FeedInterface $feed) {}

  /**
   * Stub for plugins implementing FeedPluginFormInterface.
   *
   * @see \Drupal\feeds\FeedPluginFormInterface
   */
  public function feedFormSubmit(array $form, array &$form_state, FeedInterface $feed) {
    if (isset($form_state['values'][$this->pluginType()])) {
      $feed->setConfigFor($this, $form_state['values'][$this->pluginType()]);
    }
  }

  /**
   * A feed is being saved.
   */
  public function sourceSave(FeedInterface $feed) {}

  /**
   * A feed is being deleted.
   */
  public function sourceDelete(FeedInterface $feed) {}

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
  public function getConfig($key = NULL) {
    if ($key) {
      if (isset($this->config[$key])) {
        return $this->config[$key];
      }

      return NULL;
    }

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
  public function setConfig(array $config) {
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
   * Returns a unique string identifying the form.
   *
   * Plugins that want to provide configuration forms should impement
   * FormInterface themselves.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormID() {
    return 'feeds_plugin_' . $this->getPluginID() . '_form';
  }

  /**
   * Stub for plugins implementing FormInterface.
   *
   * @see \Drupal\Core\Form\FormInterface
   */
  public function buildForm(array $form, array &$form_state) {
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#weight' => 100,
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * Stub for plugins implementing FormInterface.
   *
   * @see \Drupal\Core\Form\FormInterface
   */
  public function validateForm(array &$form, array &$form_state) {}

  /**
   * Stub for plugins implementing FormInterface.
   *
   * @see \Drupal\Core\Form\FormInterface
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->addConfig($form_state['values']);
    $this->importer->save();
    drupal_set_message(t('Your changes have been saved.'));
  }

}
