<?php

/**
 * @file
 * Definition of Drupal\feeds\FeedStorageController.
 */

namespace Drupal\feeds;

use Drupal\Core\Entity\DatabaseStorageControllerNG;
use Drupal\Core\Entity\EntityInterface;

/**
 * Controller class for Feed entities.
 */
class FeedStorageController extends DatabaseStorageControllerNG {

  /**
   * {@inheritdoc}
   */
  public function baseFieldDefinitions() {
    $properties['fid'] = array(
      'label' => t('Feed ID'),
      'description' => t('The feed ID.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $properties['uuid'] = array(
      'label' => t('UUID'),
      'description' => t('The feed UUID.'),
      'type' => 'string_field',
      'read-only' => TRUE,
    );
    $properties['importer'] = array(
      'label' => t('Importer'),
      'description' => t('The feeds importer.'),
      'type' => 'string_field',
      'read-only' => TRUE,
    );
    $properties['title'] = array(
      'label' => t('Title'),
      'description' => t('The title of this feed, always treated as non-markup plain text.'),
      'type' => 'string_field',
    );
    $properties['uid'] = array(
      'label' => t('User ID'),
      'description' => t('The user ID of the feed author.'),
      'type' => 'entity_reference_field',
      'settings' => array('target_type' => 'user'),
    );
    $properties['status'] = array(
      'label' => t('Import status'),
      'description' => t('A boolean indicating whether the feed is active.'),
      'type' => 'boolean_field',
    );
    $properties['created'] = array(
      'label' => t('Created'),
      'description' => t('The time that the feed was created.'),
      'type' => 'integer_field',
    );
    $properties['changed'] = array(
      'label' => t('Changed'),
      'description' => t('The time that the feed was last edited.'),
      'type' => 'integer_field',
    );
    $properties['imported'] = array(
      'label' => t('Imported'),
      'description' => t('The time that the feed was last imported.'),
      'type' => 'integer_field',
    );
    $properties['source'] = array(
      'label' => t('Source'),
      'description' => t('The source of the feed.'),
      'type' => 'uri_field',
    );
    $properties['config'] = array(
      'label' => t('Config'),
      'description' => t('The config of the feed.'),
      'type' => 'feeds_serialized_field',
      'settings' => array('default_value' => array()),
    );
    $properties['fetcher_result'] = array(
      'label' => t('Fetcher result'),
      'description' => t('The source of the feed.'),
      'type' => 'feeds_serialized_field',
      'settings' => array('default_value' => array()),
    );
    $properties['state'] = array(
      'label' => t('State'),
      'description' => t('The source of the feed.'),
      'type' => 'feeds_serialized_field',
      'settings' => array('default_value' => array()),
    );

    return $properties;
  }

}
