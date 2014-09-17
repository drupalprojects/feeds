<?php

/**
 * @file
 * Definition of Drupal\feeds\FeedStorageController.
 */

namespace Drupal\feeds;

use Drupal\Core\Entity\FieldableDatabaseStorageController;
use Drupal\feeds\FeedInterface;

/**
 * Controller class for Feed entities.
 */
class FeedStorageController extends FieldableDatabaseStorageController {

  /**
   * Unlocks a feed.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to unlock.
   */
  public function unlockFeed(FeedInterface $feed) {
    $this->database->update($this->entityInfo->getBaseTable())
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
    $this->database->update($this->entityInfo->getBaseTable())
      ->condition('fid', $feed->id())
      ->fields(array(
        'source' => $feed->getSource(),
        'config' => serialize($feed->getConfiguration()),
      ))
      ->execute();
  }

}
