<?php

/**
 * @file
 * Contains \Drupal\feeds\Result\ParserResult.
 */

namespace Drupal\feeds\Result;

use Drupal\feeds\Feeds\Item\ItemInterface;

/**
 * The result of a parsing stage.
 */
class ParserResult extends \SplDoublyLinkedList implements ParserResultInterface {

  protected $data = array();

  public function get($field) {
    return isset($this->data[$field]) ? $this->data[$field] : NULL;
  }

  public function set($field, $value) {
    $this->data[$field] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addItem(ItemInterface $item) {
    $item->setResult($this);
    $this->push($item);
    return $this;
  }

}
