<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\FeedsPluginBag.
 */

namespace Drupal\feeds\Feeds;

use Drupal\Core\Plugin\DefaultSinglePluginBag;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\feeds\ImporterInterface;

/**
 * Provides a container for lazily loading Feeds plugins.
 */
class FeedsPluginBag extends DefaultSinglePluginBag {

  /**
   * Constructs a FeedsPluginBag.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param string $instance_id
   *   The ID of the plugin instance.
   * @param array $configuration
   *   An array of configuration.
   * @param \Drupal\feeds\ImporterInterface $importer
   *   The feed importer this plugin belongs to.
   */
  public function __construct(PluginManagerInterface $manager, $instance_id, array $configuration, ImporterInterface $importer) {
    // Sneak the importer in via configuration.
    // @todo Remove this once plugins don't need the importer.
    $configuration['importer'] = $importer;
    parent::__construct($manager, $instance_id, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function addInstanceId($id, $configuration = NULL) {
    $this->instanceId = $id;
    parent::addInstanceId($id, $configuration);
    if ($configuration !== NULL) {
      $this->setConfiguration($configuration);
    }
  }

}
