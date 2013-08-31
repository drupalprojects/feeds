<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\DataType\SerializedItem.
 */

namespace Drupal\feeds\Plugin\DataType;

use Drupal\Core\TypedData\Annotation\DataType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Field\FieldItemBase;

/**
 * Defines the 'feeds_serialized_field' entity field item.
 *
 * @DataType(
 *   id = "feeds_serialized_field",
 *   label = @Translation("Feeds serialized field"),
 *   description = @Translation("An entity field containing a Feeds related data."),
 *   list_class = "\Drupal\Core\Entity\Field\Field"
 * )
 */
class SerializedItem extends FieldItemBase {

  /**
   * Definitions of the contained properties.
   *
   * @see SerializedItem::getPropertyDefinitions()
   *
   * @var array
   */
  protected static $propertyDefinitions;

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
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
   * {@inheritdoc}
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
