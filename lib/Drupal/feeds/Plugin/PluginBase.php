<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\PluginBase.
 */

namespace Drupal\feeds\Plugin;

use Drupal\feeds\FeedInterface;
use Drupal\Component\Plugin\PluginBase as DrupalPluginBase;

/**
 * The base class for the fetcher, parser, and processor plugins.
 *
 * @todo Move source* methods to another interface.
 * @todo This class is currently a dumping ground for methods that should be
 *   implemented by other interfaces. We're working on it.
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
   * {@inheritodc}
   */
  public function sourceDefaults() {
    return array();
  }

  /**
   * Stub for plugins implementing FeedPluginFormInterface.
   *
   * @see \Drupal\feeds\FeedPluginFormInterface
   */
  public function validateFeedForm(array &$form, array &$form_state, FeedInterface $feed) {}

  /**
   * Stub for plugins implementing FeedPluginFormInterface.
   *
   * Most all plugins should get automatic submit handlers from this.
   *
   * @see \Drupal\feeds\FeedPluginFormInterface
   */
  public function submitFeedForm(array &$form, array &$form_state, FeedInterface $feed) {
    if (isset($form_state['values'][$this->pluginType()])) {
      $feed->setConfigurationFor($this, $form_state['values'][$this->pluginType()]);
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

}
