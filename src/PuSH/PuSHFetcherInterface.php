<?php

/**
 * @file
 * Contains \Drupal\feeds\PuSH\PuSHFetcherInterface.
 */

namespace Drupal\feeds\PuSH;

use Drupal\feeds\FeedInterface;

/**
 * This interface declares that the fetcher can handle realtime updates.
 *
 * This will usually be in the form of a raw string, but is implementation
 * dependent. Only fetchers that have a realtime update mechanism should
 * implement this.
 */
interface PuSHFetcherInterface {

  /**
   * Handles a PuSH fetch request via a raw string.
   *
   * Usually this will entail simply wraping the raw data in a result object and
   * returning it.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed we are acception a raw push from.
   * @param string $raw
   *   The raw data to handle.
   *
   * @return \Drupal\feeds\Result\FetcherResultInterface
   *   A fetcher result object to pass on to parsing.
   */
  public function push(FeedInterface $feed, $raw);

  /**
   * Overrides the import period set by the importer.
   *
   * @return int
   *   The import period.
   */
  public function importPeriod(FeedInterface $feed);

}
