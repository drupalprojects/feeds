<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds.
 */

namespace Drupal\feeds;

/**
 * Static service container wrapper for Feeds.
 */
class Feeds {

  public static function scheduler() {
    return \Drupal::service('feeds.scheduler');
  }

}
