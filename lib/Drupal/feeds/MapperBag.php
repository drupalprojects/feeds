<?php

/**
 * @file
 * Contains \Drupal\feeds\TargetBag.
 */

namespace Drupal\feeds;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\PluginBag;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\feeds\ImporterInterface;

/**
 * Provides a collection of feeds mappers.
 */
class MapperBag extends PluginBag {

  /**
   * The manager used to instantiate the plugins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $manager;

  /**
   * The importer the mappers belong to.
   *
   * @var \Drupal\feeds\ImporterInterface
   */
  protected $manager;

  /**
   * Constructs a MapperBag object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param array $instance_ids
   *   The ids of the plugin instances with which we are dealing.
   * @param \Drupal\feeds\ImporterInterface $importer
   *   The importer to supply to the mappers.
   */
  public function __construct(PluginManagerInterface $manager, array $instance_ids, ImporterInterface $importer) {
    $this->manager = $manager;
    $this->importer = $importer;

    $this->instanceIDs = drupal_map_assoc($instance_ids);
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    if (!$instance_id) {
      throw new PluginException(format_string("The feeds importer '@importer' did not specify a plugin.", array('@importer' => $this->importer->id())));
    }
    if (isset($this->pluginInstances[$instance_id])) {
      return;
    }

    $settings = $this->importer->getMapperConfiguration($instance_id);
    try {
      $this->pluginInstances[$instance_id] = $this->manager->createInstance($instance_id, $settings);
    }
    catch (PluginException $e) {
      $module = $settings['module'];
      // Ignore mappers belonging to disabled modules, but re-throw valid
      // exceptions when the module is enabled and the plugin is misconfigured.
      if (!$module || \Drupal::moduleHandler()->moduleExists($module)) {
        throw $e;
      }
    }
  }

}
