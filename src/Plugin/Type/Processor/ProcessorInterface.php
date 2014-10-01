<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\Type\Processor\ProcessorInterface.
 */

namespace Drupal\feeds\Plugin\Type\Processor;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\Plugin\Type\FeedsPluginInterface;
use Drupal\feeds\StateInterface;

/**
 * Interface for Feeds processor plugins.
 */
interface ProcessorInterface extends FeedsPluginInterface {

  /**
   * Skip items that exist already.
   *
   * @var int
   */
  const SKIP_EXISTING = 0;

  /**
   * Replace items that exist already.
   *
   * @var int
   */
  const REPLACE_EXISTING = 1;

  /**
   * Update items that exist already.
   *
   * @var int
   */
  const UPDATE_EXISTING = 2;

  /**
   * Processes the results from a parser.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed being imported.
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to process.
   */
  public function process(FeedInterface $feed, ItemInterface $item);

  /**
   * Called after an import is completed.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   */
  public function finishImport(FeedInterface $feed);

  /**
   * Deletes feed items older than REQUEST_TIME - $time.
   *
   * Do not invoke expire on a processor directly, but use Feed::expire()
   * instead.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to expire items for.
   * @param int $time
   *   (optional) All items produced by this configuration that are older than
   *   REQUEST_TIME - $time should be deleted. If null, the processor should use
   *   internal configuration. Defaults to null.
   *
   * @return float
   *   StateInterface::BATCH_COMPLETE if all items have been processed, a float
   *   between 0 and 0.99* indicating progress otherwise.
   *
   * @todo Move this to a separate interface.
   */
  public function expire(FeedInterface $feed, $time = NULL);

  /**
   * Counts the number of items imported by this processor.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed whos items we are counting.
   *
   * @return int
   *   The number of items imported by this feed.
   */
  public function getItemCount(FeedInterface $feed);

  /**
   * Returns the age of items that should be removed.
   *
   * @return int
   *   The unix timestamp of the age of items to be removed.
   *
   * @todo Move this to a separate interface.
   */
  public function expiryTime();

  /**
   * Declares the possible mapping targets that this processor exposes.
   *
   * @return array
   *   An array of mapping targets keyed by the target id:
   *   - target id: This is the id of the target.
   *     - name: The name of the target as displayed on the UI.
   *     - description: A helpful description of the target.
   *     - optional_unique: Set to true if this target supports unique values.
   */
  public function getMappingTargets();

}
