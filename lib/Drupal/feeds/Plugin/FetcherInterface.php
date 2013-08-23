<?php

/**
 * @file
 * Contains Drupal\feeds\Plugin\FetcherInterface.
 */

namespace Drupal\feeds\Plugin;

use Drupal\feeds\FeedInterface;

/**
 * Interface for Feeds fetchers.
 */
interface FetcherInterface extends FeedsPluginInterface {

  /**
   * Fetch content from a feed and return it.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to fetch results for.
   *
   * @return \Drupal\feeds\Result\FetcherResultInterface
   *   A fetcher result object.
   */
  public function fetch(FeedInterface $feed);

}
