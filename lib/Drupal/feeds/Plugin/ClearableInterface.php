<?php

/**
 * @file
 * Contains Drupal\feeds\Plugin\ClearableInterface.
 */

namespace Drupal\feeds\Plugin;

use Drupal\feeds\FeedInterface;

/**
 * Interface for plugins that store information related to a feed.
 */
interface ClearableInterface {

  /**
   * Removes all stored results for a feed.
   *
   * This can be implemented by any plugin type and the method will be called
   * when a feed is being cleared (having its items deleted.) This is useful
   * if the plugin caches information related to a feed.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed being cleared. Implementers should only delete items pertaining
   *   to this feed. The preferred way of determining whether an item pertains
   *   to a certain feed is by using $feed->id(). It is the plugins's
   *   responsibility to store the id of an imported item during importing.
   */
  public function clear(FeedInterface $feed);

}
