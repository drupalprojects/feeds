<?php

/**
 * @file
 * Contains \Drupal\feeds\Annotation\FeedsProcessor.
 */

namespace Drupal\feeds\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Plugin annotation object for Feeds processor plugins.
 *
 * Plugin Namespace: Feeds\Processor
 *
 * For a working example, see \Drupal\feeds\Feeds\Processor\EntityProcessor.
 *
 * @see \Drupal\feeds\Plugin\Type\FeedsPluginManager
 * @see \Drupal\feeds\Plugin\Type\Processor\ProcessorInterface
 * @see \Drupal\feeds\Plugin\Type\PluginBase
 * @see plugin_api
 *
 * @Annotation
 */
class FeedsProcessor extends Plugin {

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
