<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\Type\Fetcher\FetcherInterface.
 */

namespace Drupal\feeds\Plugin\Type\Fetcher;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\FeedsPluginInterface;

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
