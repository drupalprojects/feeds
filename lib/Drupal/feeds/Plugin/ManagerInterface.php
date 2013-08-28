<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\ManagerInterface.
 */

namespace Drupal\feeds\Plugin;

use Drupal\feeds\FeedInterface;

/**
 * Defines the Feeds manager plugin interface.
 */
interface ManagerInterface extends FeedsPluginInterface {

  /**
   * Starts a feed import.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to import.
   */
  public function startImport(FeedInterface $feed);

  /**
   * Starts clearing the items of a feed.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to clear.
   */
  public function startClear(FeedInterface $feed);

}
