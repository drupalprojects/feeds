<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\Type\Manager\ManagerInterface.
 */

namespace Drupal\feeds\Plugin\Type\Manager;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\FeedsPluginInterface;

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
