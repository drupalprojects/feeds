<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Item\BaseItem.
 */

namespace Drupal\feeds\Feeds\Item;

use Drupal\feeds\Result\ParserResultInterface;

/**
 * Defines a base item class.
 */
abstract class BaseItem implements ItemInterface {

  /**
   * The parser result.
   *
   * @var \Drupal\feeds\Result\ParserResultInterface
   */
  protected $result;

  /**
   * {@inheritdoc}
   */
  public function get($field) {
    return isset($this->$field) ? $this->$field : $this->result->get($field);
  }

  /**
   * {@inheritdoc}
   */
  public function set($field, $value) {
    $this->$field = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setResult(ParserResultInterface $result) {
    $this->result = $result;
  }

}
