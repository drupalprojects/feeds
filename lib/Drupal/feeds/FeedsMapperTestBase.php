<?php

/**
 * @file
 * Helper class with auxiliary functions for feeds mapper module tests.
 */

namespace Drupal\feeds;

use Drupal\Component\Utility\String;

/**
 * Base class for implementing Feeds Mapper test cases.
 */
class FeedsMapperTestBase extends FeedsWebTestBase {

  /**
   * Assert that a form field for the given field with the given value
   * exists in the current form.
   *
   * @param $field_name
   *   The name of the field.
   * @param $value
   *   The (raw) value expected for the field.
   * @param $index
   *   The index of the field (for q multi-valued field).
   *
   * @see FeedsMapperTestBase::getFormFieldsNames()
   * @see FeedsMapperTestBase::getFormFieldsValues()
   */
  protected function assertNodeFieldValue($field_name, $value, $index = 0) {
    $names = $this->getFormFieldsNames($field_name, $index);
    $values = $this->getFormFieldsValues($field_name, $value);
    foreach ($names as $k => $name) {
      $value = $values[$k];
      $this->assertFieldByName($name, $value, String::format('Found form field %name for %field_name with the expected value.', array('%name' => $name, '%field_name' => $field_name)));
    }
  }

  /**
   * Returns the form fields names for a given CCK field. Default implementation
   * provides support for a single form field with the following name pattern
   * <code>"field_{$field_name}[{$index}][value]"</code>
   *
   * @param $field_name
   *   The name of the CCK field.
   * @param $index
   *   The index of the field (for q multi-valued field).
   *
   * @return
   *   An array of form field names.
   */
  protected function getFormFieldsNames($field_name, $index) {
    return array("field_{$field_name}[und][{$index}][value]");
  }

  /**
   * Returns the form fields values for a given CCK field. Default implementation
   * returns a single element array with $value casted to a string.
   *
   * @param $field_name
   *   The name of the CCK field.
   * @param $value
   *   The (raw) value expected for the CCK field.
   * @return An array of form field values.
   */
  protected function getFormFieldsValues($field_name, $value) {
    return array((string) $value);
  }

  /**
   * Create a new content-type, and add a field to it. Mostly copied from
   * cck/tests/content.crud.test ContentUICrud::testAddFieldUI
   *
   * @param $settings
   *   (Optional) An array of settings to pass through to
   *   drupalCreateContentType().
   * @param $fields
   *   (Optional) an keyed array of $field_name => $field_type used to add additional
   *   fields to the new content type.
   *
   * @return
   *   The machine name of the new content type.
   *
   * @see DrupalWebTestCase::drupalCreateContentType()
   */
  final protected function createContentType(array $settings = array(), array $fields = array()) {
    $type = $this->drupalCreateContentType($settings);
    $typename = $type->type;

    // Create the fields.
    foreach ($fields as $field_name => $options) {
      if (is_string($options)) {
        $options = array('type' => $options);
      }
      $field_type = isset($options['type']) ? $options['type'] : 'text';
      $field_widget = isset($options['widget']) ? $options['widget'] : NULL;
      $settings = isset($options['settings']) ? $options['settings'] : array();
      $instance_settings = isset($options['instance_settings']) ? $options['instance_settings'] : array();
      $this->createField($typename, $field_name, $field_type, $settings, $instance_settings);
    }

    return $typename;
  }

  /**
   * Creates a new field and attached it to a content type.
   *
   * @param string $content_type
   *   The content type to attach the field to.
   * @param string $field_name
   *   The name of the field.
   * @param string $field_type
   *   The type of the field.
   * @param array $settings
   *   (optional) An array of field settings.
   * @param array $instance settings
   *   (optional) An array of field instance settings.
   */
  protected function createField($content_type, $field_name, $field_type, array $settings = array(), array $instance_settings = array()) {
    $label = $field_name . '_' . $field_type . '_label';
    $edit = array(
      'fields[_add_new_field][label]' => $label,
      'fields[_add_new_field][field_name]' => $field_name,
      'fields[_add_new_field][type]' => $field_type,
    );
    $this->drupalPost("admin/structure/types/manage/$content_type/fields", $edit, 'Save');

    // (Default) Configure the field.
    $this->drupalPost(NULL, $settings, 'Save field settings');
    $this->assertText('Updated field ' . $label . ' field settings.');

    // Field instance settings.
    $this->drupalPost(NULL, $instance_settings, 'Save settings');
    $this->assertText('Saved ' . $label . ' configuration.');
  }

  /**
   * Reuses an existing field on an importer.
   *
   * @param string $importer
   *   The importer id.
   * @param string $field_name
   *   The name of the field.
   * @param string $field_type
   *   The type of the field.
   * @param array $instance settings
   *   (optional) An array of field instance settings.
   */
  protected function reuseFeedField($importer, $field_name, $field_type, array $instance_settings = array()) {
    $label = $field_name . '_' . $field_type . '_label';
    $edit = array(
      'fields[_add_existing_field][label]' => $label,
      'fields[_add_existing_field][field_name]' => 'field_' . $field_name,
    );
    $this->drupalPost("admin/structure/feeds/manage/$importer/fields", $edit, 'Save');

    // Field instance settings.
    $this->drupalPost(NULL, $instance_settings, 'Save settings');
    $this->assertText('Saved ' . $label . ' configuration.');
  }

}
