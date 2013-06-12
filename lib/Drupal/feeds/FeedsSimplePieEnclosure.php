<?php

namespace Drupal\feeds;

class FeedsSimplePieEnclosure extends FeedsEnclosure {
  protected $simplepie_enclosure;
  private $_serialized_simplepie_enclosure;

  /**
   * Constructor requires SimplePie enclosure object.
   */
  function __construct(\SimplePie_Enclosure $enclosure) {
    $this->simplepie_enclosure = $enclosure;
  }

  /**
   * Serialization helper.
   *
   * Handle the simplepie enclosure class seperately ourselves.
   */
  public function __sleep() {
    $this->_serialized_simplepie_enclosure = serialize($this->simplepie_enclosure);
    return array('_serialized_simplepie_enclosure');
  }

  /**
   * Unserialization helper.
   *
   * Ensure that the simplepie class definitions are loaded for the enclosure when unserializing.
   */
   public function __wakeup() {
     feeds_include_simplepie();
     $this->simplepie_enclosure = unserialize($this->_serialized_simplepie_enclosure);
  }

  /**
   * Override parent::getValue().
   */
  public function getValue() {
    return $this->simplepie_enclosure->get_link();
  }

  /**
   * Override parent::getMIMEType().
   */
  public function getMIMEType() {
    return $this->simplepie_enclosure->get_real_type();
  }
}
