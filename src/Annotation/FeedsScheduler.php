<?php

/**
 * @file
 * Contains \Drupal\feeds\Annotation\FeedsScheduler.
 */

namespace Drupal\feeds\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Plugin annotation object for Feeds scheduler plugins.
 *
 * Plugin Namespace: Feeds\Scheduler
 *
 * For a working example, see \Drupal\feeds\Feeds\Scheduler\Periodic.
 *
 * @see \Drupal\feeds\Plugin\Type\FeedsPluginManager
 * @see \Drupal\feeds\Plugin\Type\Scheduler\SchedulerInterface
 * @see \Drupal\feeds\Plugin\Type\PluginBase
 * @see plugin_api
 *
 * @Annotation
 */
class FeedsScheduler extends Plugin {

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

}
