<?php

namespace Drupal\feeds;

/**
 * Defines an element of a parsed result. Such an element can be a simple type,
 * a complex type (derived from FeedsElement) or an array of either.
 *
 * @see FeedsEnclosure
 */
class FeedsElement {
  // The standard value of this element. This value can contain be a simple type,
  // a FeedsElement or an array of either.
  protected $value;

  /**
   * Constructor.
   */
  public function __construct($value) {
    $this->value = $value;
  }

  /**
   * @todo Make value public and deprecate use of getValue().
   *
   * @return
   *   Value of this FeedsElement represented as a scalar.
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * Magic method __toString() for printing and string conversion of this
   * object.
   *
   * @return
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
