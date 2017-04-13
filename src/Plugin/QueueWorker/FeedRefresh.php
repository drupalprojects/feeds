<?php

namespace Drupal\feeds\Plugin\QueueWorker;

use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\FetchEvent;
use Drupal\feeds\Event\InitEvent;
use Drupal\feeds\Exception\LockException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\StateInterface;

/**
 * @QueueWorker(
 *   id = "feeds_feed_import",
 *   title = @Translation("Feed refresh"),
 *   cron = {"time" = 60},
 *   deriver = "Drupal\feeds\Plugin\Derivative\FeedQueueWorker"
 * )
 */
class FeedRefresh extends FeedQueueWorkerBase {

  /**
   * Parameter passed when starting a new import.
   *
   * @var string
   */
  const BEGIN = 'begin';

  /**
   * Parameter passed when continuing an import.
   *
   * @var string
   */
  const RESUME = 'resume';

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $operation = static::BEGIN;
    $feed = $data;

    // @todo Backwards compat check. Remove at later date.
    if (is_array($data)) {
      list($feed, $operation) = $feed;
    }

    if (!$feed instanceof FeedInterface) {
      return;
    }

    if ($operation === static::BEGIN) {
      try {
        $feed->lock();
      }
      catch (LockException $e) {
        return;
      }

      $feed->clearStates();
    }
    $switcher = $this->switchAccount($feed);

    try {
      $this->dispatchEvent(FeedsEvents::INIT_IMPORT, new InitEvent($feed, 'fetch'));
      $fetch_event = $this->dispatchEvent(FeedsEvents::FETCH, new FetchEvent($feed));
      $feed->setState(StateInterface::PARSE, NULL);

      $feed->saveStates();
      $this->queueFactory->get('feeds_feed_parse:' . $feed->bundle())
        ->createItem([$feed, $fetch_event->getFetcherResult()]);
    }
    catch (\Exception $exception) {
      return $this->handleException($feed, $exception);
    }
    finally {
      $switcher->switchBack();
    }
  }

}
