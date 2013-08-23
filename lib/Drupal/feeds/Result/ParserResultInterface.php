<?php

/**
 * @file
 * Contains \Drupal\feeds\Result\ParserResultInterface.
 */

namespace Drupal\feeds;

/**
 * The result of a parsing stage.
 */
interface ParserResultInterface {

  /**
   * Returns the next item to process.
   *
   * @return array
   *   Next available item or null if there is none. Every returned item is
   *   removed from the internal array.
   */
  public function shiftItem();

  /**
   * Returns the current item being processed.
   *
   * @return array|null
   *   The current result item, or null if there is no item.
   */
  public function currentItem();

}
