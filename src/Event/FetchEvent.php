<?php

/**
 * @file
 * Contains \Drupal\feeds\Event\FetchEvent.
 */

namespace Drupal\feeds\Event;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\Result\FetcherResultInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 *
 */
class FetchEvent extends Event {

  /**
   * The feed being imported.
   *
   * @var \Drupal\feeds\FeedInterface
   */
  protected $feed;

  protected $fetcherResult;

  /**
   * @param \Drupal\feeds\FeedInterface $feed
   */
  public function __construct(FeedInterface $feed) {
    $this->feed = $feed;
  }

  public function getFeed() {
    return $this->feed;
  }

  public function getFetcherResult() {
    return $this->fetcherResult;
  }

  public function setFetcherResult(FetcherResultInterface $result) {
    $this->fetcherResult = $result;
  }

}
