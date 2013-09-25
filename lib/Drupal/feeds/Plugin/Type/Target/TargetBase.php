<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\Type\Target\TargetBase.
 */

namespace Drupal\feeds\Plugin\Type\Target;

use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\ImporterInterface;
use Drupal\feeds\Plugin\Type\ConfigurablePluginBase;
use Drupal\feeds\Plugin\Type\Target\TargetInterface;

/**
 * @todo Document this.
 */
abstract class TargetBase extends ConfigurablePluginBase implements TargetInterface {

  /**
   * Target settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * Constructs a TargetBase object.
   *
   * @param array $settings
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $settings, $plugin_id, array $plugin_definition) {
    // Do not call parent, we handle everything ourselves.
    $this->importer = $settings['importer'];
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;

    // Calling setConfiguration() ensures the configuration is clean and
    // defaults are set.
    if (isset($settings['configuration'])) {
      $this->setConfiguration($settings['configuration']);
    }
    else {
      $this->setConfiguration(array());
    }
    unset($settings['importer']);
    unset($settings['configuration']);
    unset($settings['id']);
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, array &$form_state) {
    $delta = $form_state['triggering_element']['#delta'];
    $configuration = $form_state['values']['mappings'][$delta]['settings'];
    // $configuration = $form_state['values'][$this->pluginType()]['configuration'];
    $this->setConfiguration($configuration);
  }

}
