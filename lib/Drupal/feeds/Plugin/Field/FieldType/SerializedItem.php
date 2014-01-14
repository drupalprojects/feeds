<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\Field\FieldType\SerializedItem.
 */

namespace Drupal\feeds\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'feeds_serialized' entity field item.
 *
 * @FieldType(
 *   id = "feeds_serialized",
 *   label = @Translation("Feeds serialized field"),
 *   description = @Translation("An entity field containing a Feeds related data."),
 *   configurable = FALSE
 * )
 */
class SerializedItem extends FieldItemBase {

  /**
   * Definitions of the contained properties.
   *
   * @see self::getPropertyDefinitions()
   *
   * @var array
   */
  protected static $propertyDefinitions;

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {

    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions['value'] = DataDefinition::create('map')
        ->setLabel(t('Serialized value'));
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
      if (!is_array($values)) {
        $values = array();
      }
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

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ),
      ),
    );
  }

}
