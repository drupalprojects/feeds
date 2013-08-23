<?php

/**
 * @file
 * Contains \Drupal\feeds\FeedsState.
 */

namespace Drupal\feeds;

/**
 * Status of the import or clearing operation of a Feed.
 */
class FeedsState {

  /**
   * Denotes the progress made.
   *
   * 0.0 meaning no progress. 1.0 = FEEDS_BATCH_COMPLETE meaning finished.
   *
   * @var float
   */
  public $progress FEEDS_BATCH_COMPLETE;

  /**
   * Used as a pointer to store where left off. Must be serializable.
   *
   * @var scalar
   */
  public $pointer;

  /**
   * The total number of items being processed.
   *
   * @var int
   */
  public $total = 0;

  /**
   * The number of Feed items created.
   *
   * @var int
   */
  public $created = 0;

  /**
   * The number of Feed items updated.
   *
   * @var int
   */
  public $updated = 0;

  /**
   * The number of Feed items deleted.
   *
   * @var int
   */
  public $deleted = 0;

  /**
   * The number of Feed items skipped.
   *
   * @var int
   */
  public $skipped = 0;

  /**
   * The number of failed Feed items.
   *
   * @var int
   */
  public $failed = 0;

  /**
   * Reports the progress of a batch.
   *
   * When $total == $progress, the state of the task tracked by this state is
   * regarded to be complete.
   *
   * Handles the following cases gracefully:
   * - $total is 0.
   * - $progress is larger than $total.
   * - $progress approximates $total so that $finished rounds to 1.0.
   *
   * @param int $total
   *   A number that is the total to be worked off.
   * @param int $progress
   *   A number that is the progress made on $total.
   */
  public function progress($total, $progress) {
    if ($progress > $total) {
      $this->progress = FEEDS_BATCH_COMPLETE;
    }
    elseif ($total) {
      $this->progress = $progress / $total;
      if ($this->progress == FEEDS_BATCH_COMPLETE && $total != $progress) {
        $this->progress = 0.99;
      }
    }
    else {
      $this->progress = FEEDS_BATCH_COMPLETE;
    }
  }

}
