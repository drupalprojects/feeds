<?php

namespace Drupal\feeds;

/**
 * A geo term element.
 */
class FeedsGeoTermElement extends FeedsTermElement {
  public $lat, $lon, $bound_top, $bound_right, $bound_bottom, $bound_left, $geometry;
  /**
   * @param $term
   *   An array or a stdClass object that is a Drupal taxonomy term. Can include
   *   geo extensions.
   */
  public function __construct($term) {
    parent::__construct($term);
  }
}
