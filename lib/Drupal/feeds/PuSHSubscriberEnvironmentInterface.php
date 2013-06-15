<?php

namespace Drupal\feeds;

/**
 * Implement to provide environmental functionality like user messages and
 * logging.
 */
interface PuSHSubscriberEnvironmentInterface {
  /**
   * A message to be displayed to the user on the current page load.
   *
   * @param $msg
   *   A string that is the message to be displayed.
   * @param $level
   *   A string that is either 'status', 'warning' or 'error'.
   */
  public function msg($msg, $level = 'status');

  /**
   * A log message to be logged to the database or the file system.
   *
   * @param $msg
   *   A string that is the message to be displayed.
   * @param $level
   *   A string that is either 'status', 'warning' or 'error'.
   */
  public function log($msg, $level = 'status');
}
