<?php

/**
 * @file
 * Contains \Drupal\feeds\Entity\ImporterInterface.
 */

namespace Drupal\feeds;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a feeds importer entity.
 *
 * An importer is a wrapper around a set of configured plugins that are used to
 * perform an import. The importer manages the configuration on behalf of the
 * plugins.
 */
interface ImporterInterface extends ConfigEntityInterface {

  /**
   * Reports how many items should be created on one page load by this importer.
   *
   * It depends on whether the parser implements batching if this limit is
   * actually respected. If no limit is reported it doesn't mean that the number
   * of items that can be created on one page load is actually without limit.
   *
   * @return int
   *   A positive integer defining the number of items that can be created on
   *   one page load, or 0 if this number is unlimited.
   */
  public function getLimit();

  /**
   * Returns the mappings for this importer.
   *
   * @return array
   *   The list of mappings.
   */
  public function getMappings();

  /**
   * Returns the list of supported plugin types.
   *
   * @return array
   *   The list of plugin types.
   */
  public function getPluginTypes();

  /**
   * Returns the configured plugins for this importer.
   *
   * @return \Drupal\feeds\Plugin\Type\PluginBase[]
   *   An array of plugins keyed by plugin type.
   */
  public function getPlugins();

  /**
   * Returns the configured fetcher for this importer.
   *
   * @return \Drupal\feeds\Plugin\Type\Fetcher\FetcherInterface
   *   The fetcher associated with this Importer.
   */
  public function getFetcher();

  /**
   * Returns the configured parser for this importer.
   *
   * @return \Drupal\feeds\Plugin\Type\Parser\ParserInterface
   *   The parser associated with this Importer.
   */
  public function getParser();

  /**
   * Returns the configured processor for this importer.
   *
   * @return \Drupal\feeds\Plugin\Type\Processor\ProcessorInterface
   *   The processor associated with this Importer.
   */
  public function getProcessor();

  /**
   * Returns the configured plugin for this importer given the plugin type.
   *
   * @param string $plugin_type
   *   The plugin type to return.
   *
   * @return \Drupal\feeds\Plugin\PluginInterface
   *   The plugin specified.
   */
  public function getPlugin($plugin_type);

  /**
   * Sets a plugin.
   *
   * @param string $plugin_type
   *   The type of plugin being set.
   * @param string $plugin_id
   *   A id of the plugin being set.
   *
   * @return self
   *   Returns the importer.
   */
  public function setPlugin($plugin_type, $plugin_id);

  /**
   * Reschedules one or all importers.
   *
   * @param string $importer_id
   *   If true, all importers will be rescheduled, if false, no importers will
   *   be rescheduled, if an importer id, only the importer of that id will be
   *   rescheduled.
   *
   * @return bool|array
   *   Returns true if all importers need rescheduling, false if no rescheduling
   *   is required, or an array of importers that need rescheduling.
   */
  public static function reschedule($importer_id = NULL);

}
