<?php

/**
 * @file
 * Contains \Drupal\feeds\FeedsParserResult.
 */

namespace Drupal\feeds;

/**
 * The result of a parsing stage.
 */
class FeedsParserResult {

  public $title;
  public $description;
  public $link;
  public $items;
  public $currentItem;

  /**
   * Constructs a FeedsParserResult object.
   *
   * @param array $items
   *   (optional) The feed items to process. Defaults to an empty array.
   */
  public function __construct(array $items = array()) {
    $this->title = '';
    $this->description = '';
    $this->link = '';
    $this->items = $items;
  }

  /**
   * Returns the next item to process.
   *
   * @return array
   *   Next available item or NULL if there is none. Every returned item is
   *   removed from the internal array.
   *
   * @todo Move to a nextItem() based approach, not consuming the item array.
   *   Can only be done once we don't cache the entire batch object between page
   *   loads for batching anymore.
   */
  public function shiftItem() {
    $this->currentItem = array_shift($this->items);
    return $this->currentItem;
  }

  /**
   * Returns the current item being processed.
   *
   * @return array
   *   Current result item.
   */
  public function currentItem() {
    return empty($this->currentItem) ? NULL : $this->currentItem;
  }

}
