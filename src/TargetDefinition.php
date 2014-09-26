<?php

/**
 * @file
 * Contains \Drupal\feeds\TargetDefinition.
 */

namespace Drupal\feeds;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * A generic target definition.
 */
class TargetDefinition implements TargetDefinitionInterface {

  /**
   * The definition label.
   *
   * @var string
   */
  protected $label;

  /**
   * The definition description.
   *
   * @var string
   */
  protected $description;

  /**
   * The definition properties.
   *
   * @var array
   */
  protected $properties = [];

  /**
   * The unique properties.
   *
   * @var array
   */
  protected $unique = [];

  /**
   * {@inheritdoc}
   */
  public static function create() {
    return new static();
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->pluginId;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginId($plugin_id) {
    $this->pluginId = $plugin_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function hasProperty($property) {
    return isset($this->properties[$property]);
  }

  /**
   * {@inheritdoc}
   */
  public function getProperties() {
    return array_keys($this->properties);
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyLabel($property) {
    return $this->properties[$property]['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDescription($property) {
    return $this->properties[$property]['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function isUnique($property) {
    return isset($this->unique[$property]);
  }

  /**
   * Adds a supported property.
   *
   * @param string $property
   *   The supported property.
   * @param string $label
   *   (optional) The label of the property. Defaults to an empty string.
   * @param string $description
   *   (optional) The description of the property. Defaults to an empty string.
   *
   * @return $this
   */
  public function addProperty($property, $label = '', $description = '') {
    $this->properties[$property] = ['label' => $label, 'description' => ''];
    return $this;
  }

  /**
   * Marks a propert as unique.
   *
   * @param string $property
   *   The propert to mark as unique.
   *
   * @return $this
   */
  public function markPropertyUnique($property) {
    $this->unique[$property] = TRUE;
    return $this;
  }

}
