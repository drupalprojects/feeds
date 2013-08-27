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
  public function unlock(FeedInterface $feed) {
    $this->database->update('feeds_feed')
      ->condition('fid', $feed->id())
      ->fields(array('state' => FALSE))
      ->execute();
  }

}
