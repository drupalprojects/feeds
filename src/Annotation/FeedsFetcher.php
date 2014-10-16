<?php

/**
 * @file
 * Contains \Drupal\feeds\Annotation\FeedsFetcher.
 */

namespace Drupal\feeds\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Plugin annotation object for Feeds fetcher plugins.
 *
 * Plugin Namespace: Feeds\Fetcher
 *
 * For a working example, see \Drupal\feeds\Feeds\Fetcher\HttpFetcher.
 *
 * @see \Drupal\feeds\Plugin\Type\FeedsPluginManager
 * @see \Drupal\feeds\Plugin\Type\Fetcher\FetcherInterface
 * @see \Drupal\feeds\Plugin\Type\PluginBase
 * @see plugin_api
 *
 * @Annotation
 */
class FeedsFetcher extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The title of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * An optional form class that is separate from the plugin.
   *
   * It must implement \Drupal\feeds\Plugin\Type\ExternalPluginFormInterface.
   *
   * @var string
   */
  public $configuration_form;

}
