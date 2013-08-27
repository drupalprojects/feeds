<?php

/**
 * @file
 * Contains \Drupal\feeds\Result\ParserResultInterface.
 */

namespace Drupal\feeds\Result;

/**
 * The result of a parsing stage.
 *
 * @todo Move the other items from ParserResult to methods on this interface so
 *   that processors can depend on them.
 */
interface ParserResultInterface {

  /**
   * Returns the next item to process.
   *
   * @return array|null
   *   The next available item or null if there isn't one.
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
