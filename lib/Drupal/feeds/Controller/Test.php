<?php

/**
 * @file Contains \Drupal\feeds\Controller\Test.
 */

namespace Drupal\feeds\Controller;

use Drupal\feeds\FeedInterface;

class Test {

  /**
   * Executes a callback.
   *
   * @param \Drupal\feeds\FeedInterface $feeds_feed
   *   The Feed we are executing a job for.
   */
  public function execute(FeedInterface $feeds_feed) {
    sleep(20);
    watchdog('feeds_debug', 'awesome');
    watchdog('feeds_debug', strlen(file_get_contents('php://input')));
  }

}
