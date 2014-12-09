<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\FeedsSingleLazyPluginCollection.
 */

namespace Drupal\feeds\Feeds;

use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\feeds\FeedTypeInterface;

/**
 * Provides a container for lazily loading Feeds plugins.
 */
class FeedsSingleLazyPluginCollection extends DefaultSingleLazyPluginCollection {

  /**
   * Constructs a FeedsSingleLazyPluginCollection.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param string $instance_id
   *   The ID of the plugin instance.
   * @param array $configuration
   *   An array of configuration.
   * @param \Drupal\feeds\FeedTypeInterface $feed_type
   *   The feed feed type this plugin belongs to.
   */
  public function __construct(PluginManagerInterface $manager, $instance_id, array $configuration, FeedTypeInterface $feed_type) {
    // Sneak the feed type in via configuration.
    // @todo Remove this once plugins don't need the type.
    $configuration['feed_type'] = $feed_type;
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
