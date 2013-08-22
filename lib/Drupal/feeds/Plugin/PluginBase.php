<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\PluginBase.
 */

namespace Drupal\feeds\Plugin;

use Drupal\feeds\FeedInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginBase as DrupalPluginBase;

/**
 * Implement source interface for all plugins.
 *
 * Note how this class does not attempt to store source information locally.
 * Doing this would break the model where source information is represented by
 * an object that is being passed into a FeedInterface object and its plugins.
 */
abstract class PluginBase extends DrupalPluginBase implements ConfigurablePluginInterface {
  protected $id;
  // Holds the actual configuration information.
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
    $this->importer = $configuration['importer'];
    $this->id = $this->importer->id();
    unset($configuration['importer']);

    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->configuration += $this->getConfigurationDefaults();
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
   * {@inheritdoc}
   */
  public function getConfiguration($key = NULL) {
    if ($key) {
      if (isset($this->config[$key])) {
        return $this->config[$key];
      }

      return NULL;
    }

    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->getConfigurationDefaults();
  }

  /**
   * Returns default configuration.
   *
   * @return array
   *   Array where keys are the variable names of the configuration elements and
   *   values are their default values.
   */
  public function getConfigurationDefaults() {
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
  public function validateForm(array &$form, array &$form_state) {}

  /**
   * Stub for plugins implementing FormInterface.
   *
   * @see \Drupal\Core\Form\FormInterface
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->setConfiguration($form_state['values'][$this->pluginType()]);
  }

}
