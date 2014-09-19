<?php

/**
 * @file
 * Definition of Drupal\feeds\FeedStorageController.
 */

namespace Drupal\feeds;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\feeds\FeedInterface;

/**
 * Controller class for Feed entities.
 */
class FeedStorageController extends SqlContentEntityStorage {

  /**
   * Unlocks a feed.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to unlock.
   */
  public function unlockFeed(FeedInterface $feed) {
    $this->database->update($this->getBaseTable())
      ->condition('fid', $feed->id())
      ->fields(array('state' => FALSE))
      ->execute();
  }

}
