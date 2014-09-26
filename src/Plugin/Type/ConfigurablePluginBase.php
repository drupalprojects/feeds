<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\Type\ConfigurablePluginBase.
 */

namespace Drupal\feeds\Plugin\Type;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\feeds\Plugin\Type\PluginBase;

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
   *   The plugin id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    // Do not call parent, we handle everything ourselves.
    $this->importer = $configuration['importer'];
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;

    // Calling setConfiguration() ensures the configuration is clean and
    // defaults are set.
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration($key = NULL) {
    if ($key === NULL) {
      return $this->configuration;
    }

    if (isset($this->configuration[$key])) {
      return $this->configuration[$key];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $defaults = $this->defaultConfiguration();
    $this->configuration = array_intersect_key($configuration, $defaults) + $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Validation is optional.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $configuration = $form_state->getValue(array($this->pluginType(), 'configuration'));
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return array();
  }

}
