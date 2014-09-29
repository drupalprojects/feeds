<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\Field\FieldType\FeedsItem.
 */

namespace Drupal\feeds\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'feeds_item' field type.
 *
 * @FieldType(
 *   id = "feeds_item",
 *   label = @Translation("Feed"),
 *   description = @Translation("Blah blah blah."),
 *   instance_settings = {
 *     "title" = "1"
 *   },
 *   default_widget = "hidden",
 *   default_formatter = "hidden",
 *   no_ui = true
 * )
 */
class FeedsItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return array(
      'target_type' => 'feeds_feed',
    ) + parent::defaultStorageSettings();
  }


  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['imported'] = DataDefinition::create('timestamp')
      ->setLabel(t('Timestamp'));

    $properties['url'] = DataDefinition::create('uri')
      ->setLabel(t('Item URL'));

    $properties['guid'] = DataDefinition::create('string')
      ->setLabel(t('Item GUID'));

    $properties['hash'] = DataDefinition::create('string')
      ->setLabel(t('Item hash'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'target_id' => array(
          'description' => 'The ID of the target feed.',
          'type' => 'int',
          'not null' => TRUE,
          'unsigned' => TRUE,
        ),
        'imported' => array(
          'type' => 'int',
          'not null' => TRUE,
          'unsigned' => TRUE,
          'description' => 'Import date of the feed item, as a Unix timestamp.',
        ),
        'url' => array(
          'type' => 'text',
          'description' => 'Link to the feed item.',
        ),
        'guid' => array(
          'type' => 'text',
          'description' => 'Unique identifier for the feed item.',
        ),
        'hash' => array(
          'type' => 'varchar',
          // The length of an MD5 hash.
          'length' => 32,
          'not null' => TRUE,
          'description' => 'The hash of the feed item.',
        ),
      ),
      'indexes' => array(
        'target_id' => array('target_id'),
        'lookup_url' => array('target_id', array('url', 128)),
        'lookup_guid' => array('target_id', array('guid', 128)),
        'imported' => array('imported'),
      ),
      'foreign keys' => array(
        'target_id' => array(
          'table' => 'feeds_feed',
          'columns' => array('target_id' => 'fid'),
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $this->url = trim($this->url);
    $this->guid = trim($this->guid);

    // Force the imported time.
    $this->imported = REQUEST_TIME;
  }

}
