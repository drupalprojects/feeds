<?php

/**
 * @file
 * Contains \Drupal\feeds\StateInterface.
 */

namespace Drupal\feeds;

/**
 * Status of the import or clearing operation of a Feed.
 */
interface StateInterface {

  /**
   * Batch operation complete.
   *
   * @var floar
   */
  const BATCH_COMPLETE = 1.0;

  /**
   * The start time key.
   *
   * @var string
   */
  const START = 'start_time';

  /**
   * Denotes the fetch stage.
   *
   * @var string
   */
  const FETCH = 'fetch';

  /**
   * Denotes the parse stage.
   *
   * @var string
   */
  const PARSE = 'parse';

  /**
   * Denotes the process stage.
   *
   * @var string
   */
  const PROCESS = 'process';

  /**
   * Denotes the clear stage.
   *
   * @var string
   */
  const CLEAR = 'clear';

  /**
   * Denotes the expire stage.
   *
   * @var string
   */
  const EXPIRE = 'expire';

  /**
   * Reports the progress of a batch.
   *
   * When $total == $progress, the state of the task tracked by this state is
   * regarded to be complete.
   *
   * Should handle the following cases gracefully:
   * - $total is 0.
   * - $progress is larger than $total.
   * - $progress approximates $total so that $finished rounds to 1.0.
   *
   * @param int $total
   *   A number that is the total to be worked off.
   * @param int $progress
   *   A number that is the progress made on $total.
   */
  public function progress($total, $progress);

}
