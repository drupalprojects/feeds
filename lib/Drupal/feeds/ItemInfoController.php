<?php

/**
 * @file
 * Contains \Drupal\feeds\ItemInfoController.
 */

namespace Drupal\feeds;

use Drupal\Core\Entity\EntityInterface;

class ItemInfoController implements ItemInfoControllerInterface {

  /**
   * {@inheritdoc}
   */
  public function load(EntityInterface $entity) {
    return db_select('feeds_item')
      ->fields('feeds_item')
      ->condition('entity_type', $entity->entityType())
      ->condition('entity_id', $entity->id())
      ->range(0, 1)
      ->execute()
      ->fetchObject();
  }

  /**
   * {@inheritdoc}
   */
  public function insert(EntityInterface $entity) {
    if (isset($entity->feeds_item)) {
      $entity->feeds_item->entity_id = $entity->id();
      drupal_write_record('feeds_item', $entity->feeds_item);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(EntityInterface $entity) {
    if (isset($entity->feeds_item)) {
      $entity->feeds_item->entity_id = $entity->id();

      if ($this->load($entity)) {
        drupal_write_record('feeds_item', $entity->feeds_item, array('entity_type', 'entity_id'));
      }
      else {
        $this->insert($entity);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete(EntityInterface $entity) {
    // // Delete any imported items produced by the source.
    db_delete('feeds_item')
      ->condition('entity_type', $entity->entityType())
      ->condition('entity_id', $entity->id())
      ->execute();
  }

}
