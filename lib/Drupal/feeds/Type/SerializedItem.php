<?php

/**
 * @file
 * Contains \Drupal\feeds\Type\SerializedItem.
 */

namespace Drupal\feeds\Type;

use Drupal\Core\Entity\Field\FieldItemBase;

/**
 * Defines the 'feeds_serialized_field' entity field item.
 */
class SerializedItem extends FieldItemBase {

  /**
   * Definitions of the contained properties.
   *
   * @see SerializedItem::getPropertyDefinitions()
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {

    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions['value'] = array(
        'type' => 'map',
        'label' => t('Serialized value'),
      );
    }

    return static::$propertyDefinitions;
  }


  /**
   * Overrides \Drupal\Core\TypedData\TypedData::setValue().
   *
   * @param array|null $values
   *   An array of property values.
   */
  public function setValue($values, $notify = TRUE) {
    // Treat the values as property value of the first property, if no array is
    // given.
    if (is_string($values)) {
      $values = unserialize($values);
    }

    $values = array('value' => $values);

    parent::setValue($values, $notify);
  }

  /**
   * Implements \Drupal\Core\Entity\Field\FieldItemInterface::__get().
   */
  public function __get($name) {
    // There is either a property object or a plain value - possibly for a
    // not-defined property. If we have a plain value, directly return it.
    if (isset($this->values[$name])) {
      return $this->values[$name];
    }
    elseif (isset($this->properties[$name])) {
      return $this->properties[$name]->getValue();
    }
  }

}

