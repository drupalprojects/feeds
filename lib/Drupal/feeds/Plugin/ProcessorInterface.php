<?php

/**
 * @file
 * Contains Drupal\feeds\Plugin\ProcessorInterface.
 */

namespace Drupal\feeds\Plugin;

use Drupal\feeds\Exception\AccessException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\ParserResultInterface;

/**
 * Interface for Feeds processor plugins.
 */
interface ProcessorInterface extends FeedsPluginInterface {

  /**
   * Processes the results from a parser.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed being imported.
   * @param Drupal\feeds\ParserResultInterface $parser_result
   *   The result from the parser.
   */
  public function process(FeedInterface $feed, ParserResultInterface $parser_result);

  /**
   * Removes all stored results for a feed.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   Source information for this expiry. Implementers should only delete items
   *   pertaining to this source. The preferred way of determining whether an
   *   item pertains to a certain souce is by using $source->fid. It is the
   *   processor's responsibility to store the fid of an imported item in
   *   the processing stage.
   */
  public function clear(FeedInterface $feed);

  /**
   * Reports the number of items that can be processed per call.
   *
   * 0 means 'unlimited'.
   *
   * If a number other than 0 is given, Feeds parsers that support batching
   * will only deliver this limit to the processor.
   *
   * @return int
   *   The number of items to process in a single batch, or 0 for unlimited.
   *
   * @todo This should be an importer level option.
   */
  public function getLimit();

  /**
   * Deletes feed items older than REQUEST_TIME - $time.
   *
   * Do not invoke expire on a processor directly, but use Feed::expire()
   * instead.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to expire items for.
   *
   * @param int $time
   *   (optional) All items produced by this configuration that are older than
   *   REQUEST_TIME - $time should be deleted. If null, the processor should use
   *   internal configuration. Defaults to null.
   *
   * @return float
   *   FEEDS_BATCH_COMPLETE if all items have been processed, a float between 0
   *   and 0.99* indicating progress otherwise.
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
  public function itemCount(FeedInterface $feed);

  /**
   * Returns the age of items that should be removed,
   *
   * @return int
   *   The unix timestamp of the age of items to be removed.
   *
   * @todo Move this to a separate interface.
   */
  public function expiryTime();

  /**
   * Returns the mappings for this processor.
   *
   * @return array
   *   The mappings for this importer.
   *
   * @todo Processors shouldn't control mappings. They should be an importer
   * level configuration.
   */
  public function getMappings();

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

  /**
   * Sets a concrete target element.
   *
   * Invoked from ProcessorBase::map().
   *
   * @todo Move this.
   */
  public function setTargetElement(FeedInterface $feed, $target_item, $key, $value, $mapping, \stdClass $item_info);

}
