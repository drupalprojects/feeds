<?php

namespace Drupal\feeds;

/**
 * Status of an import or clearing operation on a source.
 */
class FeedsState {
  /**
   * Floating point number denoting the progress made. 0.0 meaning no progress
   * 1.0 = FEEDS_BATCH_COMPLETE meaning finished.
   */
  public $progress;

  /**
   * Used as a pointer to store where left off. Must be serializable.
   */
  public $pointer;

  /**
   * Natural numbers denoting more details about the progress being made.
   */
  public $total;
  public $created;
  public $updated;
  public $deleted;
  public $skipped;
  public $failed;

  /**
   * Constructor, initialize variables.
   */
  public function __construct() {
    $this->progress = FEEDS_BATCH_COMPLETE;
    $this->total =
    $this->created =
    $this->updated =
    $this->deleted =
    $this->skipped =
    $this->failed = 0;
  }

  /**
   * Safely report progress.
   *
   * When $total == $progress, the state of the task tracked by this state is
   * regarded to be complete.
   *
   * Handles the following cases gracefully:
   *
   * - $total is 0
   * - $progress is larger than $total
   * - $progress approximates $total so that $finished rounds to 1.0
   *
   * @param $total
   *   A natural number that is the total to be worked off.
   * @param $progress
   *   A natural number that is the progress made on $total.
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
