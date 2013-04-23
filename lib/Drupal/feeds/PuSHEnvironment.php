<?php

namespace Drupal\feeds;

/**
 * Provide environmental functions to the PuSHSubscriber library.
 */
class PuSHEnvironment implements PuSHSubscriberEnvironmentInterface {
  /**
   * Singleton.
   */
  public static function instance() {
    static $env;
    if (empty($env)) {
      $env = new PuSHEnvironment();
    }
    return $env;
  }

  /**
   * Implements PuSHSubscriberEnvironmentInterface::msg().
   */
  public function msg($msg, $level = 'status') {
    drupal_set_message(check_plain($msg), $level);
  }

  /**
   * Implements PuSHSubscriberEnvironmentInterface::log().
   */
  public function log($msg, $level = 'status') {
    switch ($level) {
      case 'error':
        $severity = WATCHDOG_ERROR;
        break;
      case 'warning':
        $severity = WATCHDOG_WARNING;
        break;
      default:
        $severity = WATCHDOG_NOTICE;
        break;
    }
    feeds_dbg($msg);
    watchdog('FeedsHTTPFetcher', $msg, array(), $severity);
  }
}
