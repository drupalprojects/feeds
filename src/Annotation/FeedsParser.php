<?php

/**
 * @file
 * Contains \Drupal\feeds\Annotation\FeedsParser.
 */

namespace Drupal\feeds\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Plugin annotation object for Feeds parser plugins.
 *
 * Plugin Namespace: Feeds\Parser
 *
 * For a working example, see \Drupal\feeds\Feeds\Parser\SyndicationParser.
 *
 * @see \Drupal\feeds\Plugin\Type\FeedsPluginManager
 * @see \Drupal\feeds\Plugin\Type\Parser\ParserInterface
 * @see \Drupal\feeds\Plugin\Type\PluginBase
 * @see plugin_api
 *
 * @Annotation
 */
class FeedsParser extends Plugin {

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
