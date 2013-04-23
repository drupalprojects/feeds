<?php

namespace Drupal\feeds;

/**
 * A result of a parsing stage.
 */
class FeedsParserResult extends FeedsResult {
  public $title;
  public $description;
  public $link;
  public $items;
  public $current_item;

  /**
   * Constructor.
   */
  public function __construct($items = array()) {
    $this->title = '';
    $this->description = '';
    $this->link = '';
    $this->items = $items;
  }

  /**
   * @todo Move to a nextItem() based approach, not consuming the item array.
   *   Can only be done once we don't cache the entire batch object between page
   *   loads for batching anymore.
   *
   * @return
   *   Next available item or NULL if there is none. Every returned item is
   *   removed from the internal array.
   */
  public function shiftItem() {
    $this->current_item = array_shift($this->items);
    return $this->current_item;
  }

  /**
   * @return
   *   Current result item.
   */
  public function currentItem() {
    return empty($this->current_item) ? NULL : $this->current_item;
  }
}
