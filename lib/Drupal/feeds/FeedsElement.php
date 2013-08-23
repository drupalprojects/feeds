<?php

/**
 * @file
 * Contains \Drupal\feeds\FeedsElement.
 */

namespace Drupal\feeds;

/**
 * Defines an element of a parsed result. Such an element can be a simple type,
 * a complex type (derived from FeedsElement) or an array of either.
 *
 * @see FeedsEnclosure
 */
class FeedsElement {
  /**
   * The standard value of this element.
   *
   * This value can contain be a simple type, a FeedsElement or an array of
   * either.
   *
   * @var mixed
   */
  protected $value;

  /**
   * Constructs a FeedsElement object.
   *
   * @param mixed $value
   *   The value of this element.
   */
  public function __construct($value) {
    $this->value = $value;
  }

  /**
   * Returns the value of this element.
   *
   * @return scalar
   *   Value of this FeedsElement represented as a scalar.
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * Converts this object to a string.
   *
   * @return string
   *   A string representation of this element.
   */
  public function __toString() {
    if (is_array($this->value)) {
      return 'Array';
    }
    if (is_object($this->value)) {
      return 'Object';
    }
    return (string) $this->getValue();
  }

}
