<?php

/**
 * @file
 * Contains \Drupal\feeds\ImporterInterface.
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
   * Indicates that a feed should never be scheduled.
   */
  const SCHEDULE_NEVER = -1;

  /**
   * Sets the label of the importer.
   *
   * @param string $label
   *   The label of the importer.
   */
  public function setLabel($label);

  /**
   * Returns the description of the importer.
   *
   * @return string
   *   The description of the importer.
   */
  public function getDescription();

  /**
   * Sets the description of the importer.
   *
   * @param string $description
   *   The description of the importer.
   */
  public function setDescription($description);

  /**
   * Returns the import period.
   *
   * @return int
   *   The import period in seconds.
   */
  public function getImportPeriod();

  /**
   * Sets the import period.
   *
   * @param int $import_period
   *   The import period in seconds.
   */
  public function setImportPeriod($import_period);

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

}
