<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\TargetInterface.
 */

namespace Drupal\feeds\Plugin;

use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\ImporterInterface;

/**
 * Interface for Feed targets.s
 */
interface TargetInterface {

  /**
   * Returns the targets defined by this plugin.
   *
   * @return array
   *   An array of targets.
   *
   * @todo Finish documenting this.
   */
  public static function targets(array &$targets, ImporterInterface $importer, array $definition);

}
