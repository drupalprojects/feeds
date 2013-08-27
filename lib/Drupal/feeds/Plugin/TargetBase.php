<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\TargetBase.
 */

namespace Drupal\feeds\Plugin;

use Drupal\Component\Plugin\PluginBase as DrupalPluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\TargetInterface;

/**
 * @todo Document this.
 */
abstract class TargetBase extends DrupalPluginBase implements TargetInterface {

  /**
   * The importer this target belongs to.
   *
   * @var \Drupal\feeds\ImporterInterface
   */
  protected $importer;

  /**
   * {@inheritdoc}
   *
   * @todo Can we inject the importer directly?
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    $this->importer = $configuration['importer'];
    unset($configuration['importer']);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  abstract public function targets();

}
