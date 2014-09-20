<?php

/**
 * @file
 * Contains \Drupal\feeds\Event\DeleteFeedsEvent.
 */

namespace Drupal\feeds\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 *
 */
class DeleteFeedsEvent extends Event {

  /**
   * The feed being imported.
   *
   * @var \Drupal\feeds\FeedInterface[]
   */
  protected $feeds;

  /**
   * @param \Drupal\feeds\FeedInterface[]
   */
  public function __construct(array $feeds) {
    $this->feeds = $feeds;
  }

  /**
   * Returns the feeds being deleted.
   *
   * @return \Drupal\feeds\FeedInterface[]
   *   A list of feeds being deleted.
   */
  public function getFeeds() {
    return $this->feeds;
  }

}
