<?php

/**
 * @file
 * Contains \Drupal\feeds\FeedExpireHandler.
 */

namespace Drupal\feeds;

use Drupal\feeds\Event\ExpireEvent;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\InitEvent;
use Drupal\feeds\FeedInterface;

/**
 * Expires the items of a feed.
 */
class FeedExpireHandler extends FeedHandlerBase {

  /**
   * {@inheritodc}
   */
  public function expire(FeedInterface $feed) {
    $this->acquireLock($feed);
    try {
      $this->dispatchEvent(FeedsEvents::INIT_EXPIRE, new InitEvent($feed));
      $this->dispatchEvent(FeedsEvents::EXPIRE, new ExpireEvent($feed));
    }
    catch (\Exception $exception) {
      // Will throw after the lock is released.
    }
    $this->releaseLock($feed);

    $result = $feed->progressExpiring();

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
