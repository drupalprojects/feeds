<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\Type\PluginBase.
 */

namespace Drupal\feeds\Plugin\Type;

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
   * The translation manager service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationManager;

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
    unset($configuration['importer']);
    $this->configuration = $configuration;
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
   *
   * @todo Get ridda this.
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
   * @see \Drupal\feeds\Plugin\Type\FeedPluginFormInterface
   */
  public function validateFeedForm(array &$form, array &$form_state, FeedInterface $feed) {}

  /**
   * Stub for plugins implementing FeedPluginFormInterface.
   *
   * Most all plugins should get automatic submit handlers from this.
   *
   * @see \Drupal\feeds\Plugin\Type\FeedPluginFormInterface
   */
  public function submitFeedForm(array &$form, array &$form_state, FeedInterface $feed) {
    if (isset($form_state['values'][$this->pluginType()])) {
      $feed->setConfigurationFor($this, $form_state['values'][$this->pluginType()]);
    }
  }

  /**
   * A feed is being saved.
   */
  public function onFeedSave(FeedInterface $feed) {}

  /**
   * A feed is being deleted.
   */
  public function onFeedDeleteMultiple(array $feeds) {}

  /**
   * Translates a string to the current language or to a given language.
   *
   * See the t() documentation for details.
   */
  protected function t($string, array $args = array(), array $options = array()) {
    return $this->getTranslationManager()->translate($string, $args, $options);
  }

  /**
   * Gets the translation manager.
   *
   * @return \Drupal\Core\StringTranslation\TranslationInterface
   *   The translation manager.
   */
  protected function getTranslationManager() {
    if (!$this->translationManager) {
      $this->translationManager = \Drupal::translation();
    }
    return $this->translationManager;
  }

  /**
   * Sets the translation manager for this form.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation_manager
   *   The translation manager.
   *
   * @return self
   *   The entity form.
   */
  public function setTranslationManager(TranslationInterface $translation_manager) {
    $this->translationManager = $translation_manager;
    return $this;
  }

  /**
   * Renders a link to a route given a route name and its parameters.
   *
   * @see \Drupal\Core\Controller\ControllerBase::l()
   *
   * @todo
   */
  protected function l($text, $route_name, array $parameters = array(), array $options = array()) {
    return \Drupal::linkGenerator()->generate($text, $route_name, $parameters, $options);
  }

  /**
   * Returns a URL give a route and its parameters.
   *
   * @todo
   */
  protected function url($name, $parameters = array(), $absolute = FALSE) {
    return \Drupal::urlGenerator()->generate($name, $parameters, $absolute);
  }

}
