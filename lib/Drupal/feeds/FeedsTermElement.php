<?php

namespace Drupal\feeds;


/**
 * Encapsulates a taxonomy style term object.
 *
 * Objects of this class can be turned into a taxonomy term style arrays by
 * casting them.
 *
 * @code
 *   $term_object = new FeedsTermElement($term_array);
 *   $term_array = (array)$term_object;
 * @endcode
 */
class FeedsTermElement extends FeedsElement {
  public $tid, $vid, $name;

  /**
   * @param $term
   *   An array or a stdClass object that is a Drupal taxonomy term.
   */
  public function __construct($term) {
    if (is_array($term)) {
      parent::__construct($term['name']);
      foreach ($this as $key => $value) {
        $this->$key = isset($term[$key]) ? $term[$key] : NULL;
      }
    }
    elseif (is_object($term)) {
      parent::__construct($term->name);
      foreach ($this as $key => $value) {
        $this->$key = isset($term->$key) ? $term->$key : NULL;
      }
    }
  }

  /**
   * Use $name as $value.
   */
  public function getValue() {
    return $this->name;
  }
}
