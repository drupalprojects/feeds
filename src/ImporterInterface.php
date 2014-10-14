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
   * Returns the description of the importer.
   *
   * @return string
   *   The description of the importer.
   */
  public function getDescription();

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
   * Sets a plugin.
   *
   * @param string $plugin_type
   *   The type of plugin being set.
   * @param string $plugin_id
   *   A id of the plugin being set.
   */
  public function setPlugin($plugin_type, $plugin_id);

  /**
   * Returns the mappings for this importer.
   *
   * @return array
   *   The list of mappings.
   */
  public function getMappings();

  /**
   * Sets the mappings for the importer.
   *
   * @param array $mappings
   *   A list of mappings.
   */
  public function setMappings(array $mappings);

  /**
   * Adds a mapping to the importer.
   *
   * @param array $mapping
   *   A single mapping.
   */
  public function addMapping(array $mapping);

  /**
   * Removes a mapping from the importer.
   *
   * @param int $delta
   *   The mapping delta to remove.
   */
  public function removeMapping($delta);

  /**
   * Removes all mappings.
   */
  public function removeMappings();

}
