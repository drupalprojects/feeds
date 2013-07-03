<?php

/**
 * @file
 * Contains \Drupal\feeds\ItemInfoControllerInterface.
 */

namespace Drupal\feeds;

interface ItemInfoControllerInterface {

  /**
   * Loads an item info object.
   *
   * @param string $entity_type
   *   The entity type.
   * @param int $entity_id
   *   The entity id.
   *
   * @return stdClass|false
   *   The item info object or false if one does not exist.
   */
  public function load($entity_type, $entity_id);

  /**
   * Inserts or updates an item info object in the feeds_item table.
   *
   * @param stdClass $item_info
   *   The item info object to save.
   */
  public function save(\stdClass $item_info);

  /**
   * Deletes item info for a given entity.
   *
   * @param string $entity_type
   *   The entity type.
   * @param int $entity_id
   *   The entity id.
   *
   * @return bool
   *   True if the item was actually deleted. False if it did not exist.
   */
  public function delete($entity_type, $entity_id);

}
