<?php

/**
 * @file
 * Contains \Drupal\feeds\FeedClearHandler.
 */

namespace Drupal\feeds;

use Drupal\feeds\Event\ClearEvent;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\InitEvent;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\StateInterface;

/**
 * Deletes the items of a feed.
 */
class FeedClearHandler extends FeedHandlerBase {

  /**
   * {@inheritodc}
   */
  public function clear(FeedInterface $feed) {
    $this->acquireLock($feed);
    try {
      $this->dispatchEvent(FeedsEvents::INIT_CLEAR, new InitEvent($feed));
      $this->dispatchEvent(FeedsEvents::CLEAR, new ClearEvent($feed));
    }
    catch (\Exception $exception) {
      // Do nothing yet.
    }
    $this->releaseLock($feed);

    // Clean up.
    $result = $feed->progressClearing();

    if ($result == StateInterface::BATCH_COMPLETE || isset($exception)) {
      $feed->clearState();
    }

    $feed->save();

    if (isset($exception)) {
      throw $exception;
    }

    return $result;
  }

}
