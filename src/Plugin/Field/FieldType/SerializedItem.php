<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\Field\FieldType\SerializedItem.
 */

namespace Drupal\feeds\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
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
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Serialized value'));

    return $properties;
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
        $values = [];
      }
    }

    $this->values = $values;
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __get($name) {
    return $this->get($name);
  }

  public function get($property_name) {
    if ($property_name === 'value') {
      return $this->values;
    }
    if (isset($this->values[$property_name])) {
      return $this->values[$property_name];
    }
    return [];
  }

  public function getValue() {
    return $this->values;
  }

  /**
   * {@inheritdoc}
   */
  public function __isset($name) {
    if ($name === 'value') {
      return TRUE;
    }
    return isset($this->values[$name]);
  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ],
      ],
    ];
  }

}
