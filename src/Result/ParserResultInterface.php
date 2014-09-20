<?php

/**
 * @file
 * Contains \Drupal\feeds\Result\ParserResultInterface.
 */

namespace Drupal\feeds\Result;

use Drupal\feeds\Feeds\Item\ItemInterface;

/**
 * The result of a parsing stage.
 */
interface ParserResultInterface extends \Iterator, \ArrayAccess, \Countable {

  /**
   * Adds an item to the result.
   *
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   A parsed feed item.
   */
  public function addItem(ItemInterface $item);

}
