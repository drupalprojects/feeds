<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\field\field_type\FeedsItem.
 */

namespace Drupal\feeds\Plugin\field\field_type;

use Drupal\Core\Entity\Annotation\FieldType;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\FieldType\ConfigFieldItemBase;
use Drupal\field\FieldInterface;

/**
 * Plugin implementation of the 'feeds_item' field type.
 *
 * @FieldType(
 *   id = "feeds_item",
 *   label = @Translation("Feeds item"),
 *   description = @Translation("Blah blah blah."),
 *   instance_settings = {
 *     "title" = "1"
 *   },
 *   default_widget = "hidden",
 *   default_formatter = "hidden",
 *   no_ui = true
 * )
 */
class FeedsItem extends ConfigFieldItemBase {

  /**
   * Definitions of the contained properties.
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions['fid'] = array(
        'type' => 'integer',
        'label' => t('Feed id'),
      );
      static::$propertyDefinitions['imported'] = array(
        'type' => 'integer',
        'label' => t('Imported timestamp'),
      );
      static::$propertyDefinitions['url'] = array(
        'type' => 'uri',
        'label' => t('Feed item URL'),
      );
      static::$propertyDefinitions['guid'] = array(
        'type' => 'string',
        'label' => t('Feed item GUID'),
      );
      static::$propertyDefinitions['hash'] = array(
        'type' => 'string',
        'label' => t('Feed item hash'),
      );
    }
    return static::$propertyDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldInterface $field) {
    return array(
      'columns' => array(
        'fid' => array(
          'description' => 'The {feeds_feed}.fid this record belongs to.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'imported' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Import date of the feed item, as a Unix timestamp.',
        ),
        'url' => array(
          'type' => 'text',
          'not null' => TRUE,
          'description' => 'Link to the feed item.',
        ),
        'guid' => array(
          'type' => 'text',
          'not null' => TRUE,
          'description' => 'Unique identifier for the feed item.',
        ),
        'hash' => array(
          'type' => 'varchar',
          // The length of an MD5 hash.
          'length' => 32,
          'not null' => TRUE,
          'default' => '',
          'description' => 'The hash of the feed item.',
        ),
      ),
      'indexes' => array(
        'fid' => array('fid'),
        'lookup_url' => array('fid', array('url', 128)),
        'lookup_guid' => array('fid', array('guid', 128)),
        'imported' => array('imported'),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function instanceSettingsForm(array $form, array &$form_state) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {

  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return !$this->get('fid')->getValue();
  }

}
