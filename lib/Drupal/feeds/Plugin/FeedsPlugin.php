<?php

/**
 * @file
 * Definition of FeedsPlugin class.
 */

namespace Drupal\feeds\Plugin;

use Drupal\feeds\FeedsSource;
use Drupal\feeds\FeedsSourceInterface;

/**
 * Base class for a fetcher, parser or processor result.
 */

/**
 * Implement source interface for all plugins.
 *
 * Note how this class does not attempt to store source information locally.
 * Doing this would break the model where source information is represented by
 * an object that is being passed into a Feed object and its plugins.
 */
abstract class FeedsPlugin extends FeedsConfigurable implements FeedsSourceInterface {

  /**
   * Constructor.
   *
   * Initialize class variables.
   */
  public function __construct($id) {
    parent::__construct($id);
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
   * Get all available plugins.
   */
  public static function all() {
    $fetchers = \Drupal::service('plugin.manager.feeds.fetcher')->getDefinitions();
    $parsers = \Drupal::service('plugin.manager.feeds.parser')->getDefinitions();
    $processors = \Drupal::service('plugin.manager.feeds.processor')->getDefinitions();
    $result = array();

    foreach ($fetchers as $key => $info) {
      if (!empty($info['hidden'])) {
        continue;
      }
      $result[$key] = $info;
    }

    foreach ($parsers as $key => $info) {
      if (!empty($info['hidden'])) {
        continue;
      }
      $result[$key] = $info;
    }

    foreach ($processors as $key => $info) {
      if (!empty($info['hidden'])) {
        continue;
      }
      $result[$key] = $info;
    }

    // Sort plugins by name and return.
    // uasort($result, 'feeds_plugin_compare');
    return $result;
  }

  /**
   * Determines whether given plugin is derived from given base plugin.
   *
   * @param $plugin_key
   *   String that identifies a Feeds plugin key.
   * @param $parent_plugin
   *   String that identifies a Feeds plugin key to be tested against.
   *
   * @return
   *   TRUE if $parent_plugin is directly *or indirectly* a parent of $plugin,
   *   FALSE otherwise.
   */
  public static function child($plugin_key, $parent_plugin) {
    $all_info = self::all();
    $info = $all_info[$plugin_key];

    return TRUE;
  }

  /**
   * Determines the type of a plugin.
   *
   * @todo PHP5.3: Implement self::type() and query with $plugin_key::type().
   *
   * @param $plugin_key
   *   String that identifies a Feeds plugin key.
   *
   * @return
   *   One of the following values:
   *   'fetcher' if the plugin is a fetcher
   *   'parser' if the plugin is a parser
   *   'processor' if the plugin is a processor
   *   FALSE otherwise.
   */
  public static function typeOf($plugin_key) {
    $fetchers = \Drupal::service('plugin.manager.feeds.fetcher')->getDefinitions();

    if (isset($fetchers[$plugin_key])) {
      return 'fetcher';
    }

    $parsers = \Drupal::service('plugin.manager.feeds.parser')->getDefinitions();

    if (isset($parsers[$plugin_key])) {
      return 'parser';
    }

    $processors = \Drupal::service('plugin.manager.feeds.processor')->getDefinitions();

    if (isset($processors[$plugin_key])) {
      return 'processor';
    }

    return FALSE;
  }

  /**
   * Gets all available plugins of a particular type.
   *
   * @param $type
   *   'fetcher', 'parser' or 'processor'
   */
  public static function byType($type) {
    $plugins = self::all();

    $result = array();
    foreach ($plugins as $key => $info) {
      if ($type == self::typeOf($key)) {
        $result[$key] = $info;
      }
    }
    return $result;
  }
}

/**
 * Used when a plugin is missing.
 */
// class FeedsMissingPlugin extends FeedsPlugin {
//   public function pluginType() {
//     return 'missing';
//   }
//   public function menuItem() {
//     return array();
//   }
// }

/**
 * Sort callback for FeedsPlugin::all().
 */
function feeds_plugin_compare($a, $b) {
  return strcasecmp($a['name'], $b['name']);
}
