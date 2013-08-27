<?php

/**
 * @file
 * Contains \Drupal\feeds\Result\ParserResult.
 */

namespace Drupal\feeds\Result;

/**
 * The result of a parsing stage.
 */
class ParserResult implements ParserResultInterface {

  /**
   * The title of the feed.
   *
   * @var string
   */
  public $title;

  /**
   * The description of the feed.
   *
   * @var $description
   */
  public $description;

  /**
   * The link of the feed.
   *
   * @var string
   */
  public $link;

  /**
   * The parsed items.
   *
   * @var array
   */
  public $items;

  /**
   * The current item being processed.
   *
   * @var array
   */
  public $currentItem;

  /**
   * Constructs a ParserResult object.
   *
   * @param array $items
   *   (optional) The feed items to process. Defaults to an empty array.
   */
  public function __construct(array $items = array()) {
    $this->items = $items;
  }

  /**
   * {@inheritdoc}
   */
  public function shiftItem() {
    $this->currentItem = array_shift($this->items);
    return $this->currentItem;
  }

  /**
   * {@inheritdoc}
   */
  public function currentItem() {
    return $this->currentItem;
  }

}
