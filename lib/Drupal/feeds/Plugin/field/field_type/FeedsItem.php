<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\field\field_type\FeedsItem.
 */

namespace Drupal\feeds\Plugin\field\field_type;

use Drupal\Core\Entity\Annotation\FieldType;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\FieldType\ConfigEntityReferenceItemBase;
use Drupal\field\FieldInterface;

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
class FeedsItem extends ConfigEntityReferenceItemBase {

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
    $this->definition['settings']['target_type'] = 'feeds_feed';
    // Definitions vary by entity type and bundle, so key them accordingly.
    $key = $this->definition['settings']['target_type'] . ':';
    $key .= isset($this->definition['settings']['target_bundle']) ? $this->definition['settings']['target_bundle'] : '';

    if (!isset(static::$propertyDefinitions[$key])) {
      static::$propertyDefinitions[$key] = parent::getPropertyDefinitions();

      static::$propertyDefinitions[$key]['imported'] = array(
        'type' => 'integer',
        'label' => t('Timestamp'),
      );
      static::$propertyDefinitions[$key]['url'] = array(
        'type' => 'uri',
        'label' => t('Item URL'),
      );
      static::$propertyDefinitions[$key]['guid'] = array(
        'type' => 'string',
        'label' => t('Item GUID'),
      );
      static::$propertyDefinitions[$key]['hash'] = array(
        'type' => 'string',
        'label' => t('Item hash'),
      );
    }

    return static::$propertyDefinitions[$key];
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldInterface $field) {
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
