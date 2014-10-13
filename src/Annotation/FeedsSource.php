<?php

/**
 * @file
 * Contains \Drupal\feeds\Annotation\FeedsSource.
 */

namespace Drupal\feeds\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Plugin annotation object for Feeds source plugins.
 *
 * Plugin Namespace: Feeds\Source
 *
 * For a working example, see \Drupal\feeds\Feeds\Source\BasicFieldSource.
 *
 * @see \Drupal\feeds\Plugin\Type\FeedsPluginManager
 * @see \Drupal\feeds\Plugin\Type\Source\SourceInterface
 * @see \Drupal\feeds\Plugin\Type\PluginBase
 * @see plugin_api
 *
 * @Annotation
 */
class FeedsSource extends Plugin {

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
   * The field types a source plugin applies to.
   *
   * @var array
   */
  public $field_types;

}
