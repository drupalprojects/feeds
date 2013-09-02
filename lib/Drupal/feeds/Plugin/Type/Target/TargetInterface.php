<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\Type\Target\TargetInterface.
 */

namespace Drupal\feeds\Plugin\Type\Target;

use Drupal\feeds\ImporterInterface;

/**
 * Interface for Feed targets.
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
