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
abstract class PluginBase extends DrupalPluginBase implements FeedsPluginInterface {

  /**
   * The impoter this plugin is working for.
   *
   * @var \Drupal\feeds\Entity\Importer
   */
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
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function pluginType() {
    return $this->pluginDefinition['plugin_type'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceElement(FeedInterface $feed, array $item, $element_key) {
    if (isset($item[$element_key])) {
      return $item[$element_key];
    }
  }

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
   * Returns a unique string identifying the form.
   *
   * Plugins that want to provide configuration forms should impement
   * FormInterface themselves.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormID() {
    return 'feeds_plugin_' . $this->getPluginId() . '_form';
  }

}
