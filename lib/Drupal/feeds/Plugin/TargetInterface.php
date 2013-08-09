<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\TargetInterface.
 */

namespace Drupal\feeds\Plugin;

use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\FeedInterface;

/**
 * Interface targets must implement.
 */
interface TargetInterface {

  /**
   * Returns the targets defined.
   *
   * @return array
   *   An array of targets.
   *
   * @todo Finish documenting this.
   */
  public function targets();

  /**
   * Sets the value on an entity.
   */
  public function setTarget(FeedInterface $feed, EntityInterface $entity, $field_name, $value, array $mapping);

}
