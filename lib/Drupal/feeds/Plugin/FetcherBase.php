<?php

/**
 * @file
 * Contains Drupal\feeds\Plugin\FetcherBase.
 */

namespace Drupal\feeds\Plugin;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedsResult;

/**
 * Abstract class, defines shared functionality between fetchers.
 */
abstract class FetcherBase extends FeedsPlugin {

  /**
   * Implements FeedsPlugin::pluginType().
   */
  public function pluginType() {
    return 'fetcher';
  }

  /**
   * Fetch content from a source and return it.
   *
   * Every class that extends FetcherBase must implement this method.
   *
   * @param $feed
   *   Source value as entered by user through feedForm().
   *
   * @return
   *   A FeedsFetcherResult object.
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

  /**
   * Request handler invoked if callback URL is requested. Locked down by
   * default. For a example usage see FeedsHTTPFetcher.
   *
   * Note: this method may exit the script.
   *
   * @return
   *   A string to be returned to the client.
   */
  public function request($fid = 0) {
    drupal_access_denied();
  }

  /**
   * Construct a path for a concrete fetcher/source combination. The result of
   * this method matches up with the general path definition in
   * FetcherBase::menuItem(). For example usage look at FeedsHTTPFetcher.
   *
   * @return
   *   Path for this fetcher/source combination.
   */
  public function path($fid = 0) {
    $id = urlencode($this->importer->id());
    if ($fid && is_numeric($fid)) {
      return "feeds/importer/$id/$fid";
    }
    return "feeds/importer/$id";
  }

  /**
   * Menu item definition for fetchers of this class. Note how the path
   * component in the item definition matches the return value of
   * FetcherBase::path();
   *
   * Requests to this menu item will be routed to FetcherBase::request().
   *
   * @return
   *   An array where the key is the Drupal menu item path and the value is
   *   a valid Drupal menu item definition.
   */
  public function menuItem() {
    return array(
      'feeds/importer/%feeds_importer' => array(
        'page callback' => 'feeds_fetcher_callback',
        'page arguments' => array(2, 3),
        'access callback' => TRUE,
        'type' => MENU_CALLBACK,
      ),
    );
  }

  /**
   * Subscribe to a source. Only implement if fetcher requires subscription.
   *
   * @param FeedInterface $feed
   *   Source information for this subscription.
   */
  public function subscribe(FeedInterface $feed) {}

  /**
   * Unsubscribe from a source. Only implement if fetcher requires subscription.
   *
   * @param FeedInterface $feed
   *   Source information for unsubscribing.
   */
  public function unsubscribe(FeedInterface $feed) {}

  /**
   * Override import period settings. This can be used to force a certain import
   * interval.
   *
   * @param $feed
   *   A FeedInterface object.
   *
   * @return
   *   A time span in seconds if periodic import should be overridden for given
   *   $feed, NULL otherwise.
   */
  public function importPeriod(FeedInterface $feed) {}

}
