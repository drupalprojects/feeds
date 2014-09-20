<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Item\SyndicationItem.
 */

namespace Drupal\feeds\Feeds\Item;

use Drupal\feeds\Result\ParserResultInterface;

/**
 * Defines an item class for use with an RSS/Atom parser.
 */
class SyndicationItem implements ItemInterface {

  protected $title;
  protected $description;
  protected $author_name;
  protected $timestamp;
  protected $url;
  protected $guid;
  protected $tags;
  protected $geolocations;
  protected $enclosures;

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
