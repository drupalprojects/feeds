<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\ConfigurablePluginBase.
 */

namespace Drupal\feeds\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\feeds\Plugin\PluginBase;

/**
 * Base class for Feeds plugins that have configuration.
 */
abstract class ConfigurablePluginBase extends PluginBase implements ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Constructs a ConfigurablePluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    // Do not call parent, we handle everything ourselves.
    $this->importer = $configuration['importer'];
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;

    // Calling setConfiguration() ensures the configuration id clean and
    // defaults are set.
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration($key = NULL) {
    if ($key) {
      if (isset($this->configuration[$key])) {
        return $this->configuration[$key];
      }

      return NULL;
    }

    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $defaults = $this->getDefaultConfiguration();
    $this->configuration = array_intersect_key($configuration, $defaults) + $defaults;
  }

  /**
   * Returns default configuration.
   *
   * @return array
   *   Array where keys are the variable names of the configuration elements and
   *   values are their default values.
   */
  abstract protected function getDefaultConfiguration();

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, array &$form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, array &$form_state) {
    $this->setConfiguration($form_state['values'][$this->pluginType()]['configuration']);
  }

}
