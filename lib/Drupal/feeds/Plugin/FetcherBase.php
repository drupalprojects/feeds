<?php

/**
 * @file
 * Contains Drupal\feeds\Plugin\FetcherBase.
 */

namespace Drupal\feeds\Plugin;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\FetcherResultInterface;

/**
 * Abstract class, defines shared functionality between fetchers.
 */
abstract class FetcherBase extends PluginBase {

  /**
   * Fetch content from a source and return it.
   *
   * Every class that extends FetcherBase must implement this method.
   *
   * @param $feed
   *   Source value as entered by user through feedForm().
   *
   * @return
   *   A \Drupal\feeds\FetcherResultInterface object.
   */
  public abstract function fetch(FeedInterface $feed);

  /**
   * Clear all caches for results for given source.
   *
   * @param FeedInterface $feed
   *   Source information for this expiry. Implementers can choose to only clear
   *   caches pertaining to this source.
   */
  public function clear(FeedInterface $feed) {}

}
