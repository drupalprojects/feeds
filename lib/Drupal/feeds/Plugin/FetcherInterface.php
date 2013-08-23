<?php

/**
 * @file
 * Contains Drupal\feeds\Plugin\FetcherInterface.
 */

namespace Drupal\feeds\Plugin;

use Drupal\feeds\FeedInterface;

/**
 * Interface for feeds fetchers.
 */
interface FetcherInterface extends FeedsPluginInterface {

  /**
   * Fetch content from a source and return it.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to fetch results for.
   *
   * @return \Drupal\feeds\Result\FetcherResultInterface
   *   A fetcher result object.
   */
  public function fetch(FeedInterface $feed);

}
