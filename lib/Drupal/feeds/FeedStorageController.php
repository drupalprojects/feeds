<?php

/**
 * @file
 * Definition of Drupal\feeds\FeedStorageController.
 */

namespace Drupal\feeds;

use Drupal\Core\Entity\DatabaseStorageControllerNG;
use Drupal\Core\Entity\EntityInterface;
use Drupal\job_scheduler\JobScheduler;

/**
 * Controller class for feeds.
 *
 * This extends the Drupal\Core\Entity\DatabaseStorageController class, adding
 * required special handling for feed entities.
 */
class FeedStorageController extends DatabaseStorageControllerNG {

  /**
   * Call PluginBase::sourceSave() on plugins.
   *
   * This is called after save() so that plugins have access to the feed id.
   *
   * @param \Drupal\Core\Entity\EntityInterface $feed
   *   The feed object.
   *
   * @todo Figure out a better way to do this.
   */
  protected function savePropertyData(EntityInterface $feed) {
    parent::savePropertyData($feed);

    // Alert implementers of FeedInterface to the fact that we're saving.
    foreach ($feed->getImporter()->getPluginTypes() as $type) {
      $feed->getImporter()->$type->sourceSave($feed);
    }
    $config = $feed->config->value;

    // Store the source property of the fetcher in a separate column so that we
    // can do fast lookups on it.
    $feed->source->value = '';
    if (isset($config[$feed->getImporter()->fetcher->getPluginID()]['source'])) {
      $feed->source->value = $config[$feed->getImporter()->fetcher->getPluginID()]['source'];
    }

    db_update('feed_feed')
      ->condition('fid', $feed->id())
      ->fields(array(
        'source' => $feed->source->value,
        'config' => $config,
      ))
      ->execute();
  }

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
