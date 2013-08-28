<?php

/**
 * @file
 * Definition of Drupal\feeds\FeedStorageController.
 */

namespace Drupal\feeds;

use Drupal\Core\Entity\DatabaseStorageControllerNG;
use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\FeedInterface;

/**
 * Controller class for Feed entities.
 */
class FeedStorageController extends DatabaseStorageControllerNG {

  /**
   * Unlocks a feed.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to unlock.
   */
  public function unlockFeed(FeedInterface $feed) {
    $this->database->update($this->entityInfo['base_table'])
      ->condition('fid', $feed->id())
      ->fields(array('state' => FALSE))
      ->execute();
  }

  /**
   * Updates the config and source fields of a feed.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to unlock.
   *
   * @todo Figure out a better way to do this.
   */
  public function updateFeedConfig(FeedInterface $feed) {
    $this->database->update($this->entityInfo['base_table'])
      ->condition('fid', $feed->id())
      ->fields(array(
        'source' => $feed->get('source')->value,
        'config' => serialize($feed->get('config')->value),
      ))
      ->execute();
  }

}
