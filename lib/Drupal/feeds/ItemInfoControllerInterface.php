<?php

/**
 * @file
 * Contains \Drupal\feeds\ItemInfoControllerInterface.
 */

namespace Drupal\feeds;

use Drupal\Core\Entity\EntityInterface;

interface ItemInfoControllerInterface {

  /**
   * Loads an item info object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to load item info for.
   *
   * @return stdClass|false
   *   The item info object or false if one does not exist.
   */
  public function load(EntityInterface $entity);

  /**
   * Inserts an item info object into the feeds_item table.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to load item info for.
   */
  public function insert(EntityInterface $entity);

  /**
   * Inserts or updates an item info object in the feeds_item table.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to load item info for.
   */
  public function save(EntityInterface $entity);

  /**
   * Deletes item info for a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to delete item info for.
   */
  public function delete(EntityInterface $entity);

}
