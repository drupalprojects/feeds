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
  public function startBatchClear(FeedInterface $feed) {
    $this->acquireLock($feed);

    $batch = [
      'title' => $this->t('Deleting %title', ['%title' => $feed->label()]),
      'init_message' => $this->t('Starting feed clear.'),
      'operations' => [
        [[get_class($this), 'contineBatchClear'], [$feed->id()]],
      ],
      'progress_message' => $this->t('Deleting %title', ['%title' => $feed->label()]),
      'finished' => [get_class($this), 'finishBatchClear'],
      'error_message' => $this->t('An error occored while deleting %title.', ['%title' => $feed->label()]),
    ];

    batch_set($batch);
    $this->releaseLock($feed);
  }

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

  /**
   * Continues a clear job.
   *
   * @param int $fid
   *   The feed id being imported.
   * @param array &$context
   *   The batch context.
   */
  public static function contineBatchClear($fid, array &$context) {
    $context['finished'] = StateInterface::BATCH_COMPLETE;
    try {
      if ($feed = entity_load('feeds_feed', $fid)) {
        $context['finished'] = $feed->clear();
      }
    }
    catch (\Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
    }
  }

  /**
   * Finish batch.
   *
   * This function is a static function to avoid serialising the Background
   * object unnecessarily.
   */
  public static function finishBatchClear($success, $results, $operations) {
    if ($success) {

    }
    else {

    }
  }

}
