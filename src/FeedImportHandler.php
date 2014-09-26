<?php

/**
 * @file
 * Contains \Drupal\feeds\FeedImportHandler.
 */

namespace Drupal\feeds;

use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\FetchEvent;
use Drupal\feeds\Event\FetcherEvent;
use Drupal\feeds\Event\InitEvent;
use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\Event\ParserEvent;
use Drupal\feeds\Event\ProcessEvent;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResultInterface;
use Drupal\feeds\Result\RawFetcherResult;
use Drupal\feeds\StateInterface;

/**
 * Runs the actual import on a feed.
 */
class FeedImportHandler extends FeedHandlerBase {

  /**
   * {@inheritodc}
   */
  public function startBatchImport(FeedInterface $feed) {
    $feed->lock();

    $batch = [
      'title' => $this->t('Importing %title', ['%title' => $feed->label()]),
      'init_message' => $this->t('Starting feed import.'),
      'operations' => [
        [[get_class($this), 'contineBatch'], [$feed->id(), 'import']],
      ],
      'progress_message' => $this->t('Importing %title', ['%title' => $feed->label()]),
      'error_message' => $this->t('An error occored while importing %title.', ['%title' => $feed->label()]),
    ];

    batch_set($batch);
  }

  /**
   * Handles a push import.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed receiving the push.
   * @param string $payload
   *   The feed contents.
   *
   * @return float
   *   The progress made.
   *
   * @todo Move this to a queue.
   */
  public function pushImport(FeedInterface $feed, $payload) {
    $feed->lock();

    $fetcher_result = new RawFetcherResult($payload);
    $feed->setFetcherResult($fetcher_result);

    do {
      $result = $this->import($feed);
    } while ($result != StateInterface::BATCH_COMPLETE);
  }

  /**
   * {@inheritodc}
   */
  public function import(FeedInterface $feed) {
    try {
      $this->dispatchEvent(FeedsEvents::INIT_IMPORT, new InitEvent($feed));

      // Fetch.
      $fetcher_result = $feed->getFetcherResult();
      if (!$fetcher_result || $feed->progressParsing() == StateInterface::BATCH_COMPLETE) {
        $fetcher_result = $this->doFetch($feed);
        $feed->setFetcherResult($fetcher_result);
        $feed->setState(StateInterface::PARSE, NULL);
      }

      // Parse.
      $parser_result = $this->doParse($feed, $fetcher_result);

      // Process.
      $this->doProcess($feed, $parser_result);
    }
    catch (EmptyFeedException $e) {
      // This isn't actually an error. It just means the feed is empty.
    }
    catch (\Exception $exception) {
      // Do nothing. Will thow later.
    }

    // Clean up.
    $result = $feed->progressImporting();

    if ($result == StateInterface::BATCH_COMPLETE || isset($exception)) {
      $feed->cleanUp();
      $feed->save();
      $feed->unlock();
    }
    else {
      $feed->save();
    }

    if (isset($exception)) {
      throw $exception;
    }

    return $result;
  }

  protected function doFetch(FeedInterface $feed) {
    $fetch_event = new FetchEvent($feed);
    $this->dispatchEvent(FeedsEvents::FETCH, $fetch_event);

    $fetcher_result = $fetch_event->getFetcherResult();
    return $fetcher_result;
  }

  protected function doParse(FeedInterface $feed, FetcherResultInterface $fetcher_result) {
    $parse_event = new ParseEvent($feed, $fetcher_result);
    $this->dispatchEvent(FeedsEvents::PARSE, $parse_event);
    return $parse_event->getParserResult();
  }

  protected function doProcess(FeedInterface $feed, ParserResultInterface $parser_result) {
    $process_event = new ProcessEvent($feed, $parser_result);
    $this->dispatchEvent(FeedsEvents::PROCESS, $process_event);
  }

}
