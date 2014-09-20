<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Item\DynamicItem.
 */

namespace Drupal\feeds\Feeds\Item;

use Drupal\feeds\Result\ParserResultInterface;

/**
 * Defines an item class for when a parser has a dynamic set of fields.
 *
 * This should be avoided unless the parser allows dynamic field.
 */
class DynamicItem implements ItemInterface {

  /**
   * The field data.
   *
   * @var array
   */
  protected $data = array();

  /**
   * {@inheritdoc}
   */
  public function get($field) {
    return isset($this->data[$field]) ? $this->data[$field] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function set($field, $value) {
    $this->data[$field] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setResult(ParserResultInterface $result) {
    $this->result = $result;
  }

}
