<?php

/**
 * @file
 * Provide views data and handlers for feeds.module.
 *
 * @ingroup views_module_handlers
 */

use Drupal\field\Entity\FieldStorageConfig;

/**
 * Implements hook_views_data().
 */
function feeds_views_data() {

  // Define the base group of this table. Fields that don't have a group defined
  // will go into this field by default.
  $data['feeds_feed']['table']['group'] = t('Feeds');

  // Advertise this table as a possible base table.
  $data['feeds_feed']['table']['base'] = array(
    'field' => 'fid',
    'title' => t('Feeds'),
    'weight' => -10,
    // 'access query tag' => 'node_access',
    'defaults' => array(
      'field' => 'title',
    ),
  );
  $data['feeds_feed']['table']['entity type'] = 'feeds_feed';
  // $data['node']['table']['wizard_id'] = 'node';

  $data['feeds_feed']['fid'] = array(
    'title' => t('Fid'),
    'help' => t('The feed ID.'),
    'field' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['feeds_feed']['title'] = array(
    'title' => t('Title'),
    'help' => t('The feed title.'),
    'field' => array(
      'id' => 'feeds_feed',
      'link_to_feed default' => TRUE,
     ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['feeds_feed']['importer'] = array(
    'title' => t('Importer'),
    'help' => t('The feed importer.'),
    'field' => array(
      'id' => 'feeds_feed_importer',
     ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'bundle',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['feeds_feed']['uid'] = array(
    'title' => t('Author uid'),
    'help' => t('The user authoring the feed. If you need more fields than the uid add the feed: author relationship'),
    'relationship' => array(
      'title' => t('Feed author'),
      'help' => t('Relate feed to the user who created it.'),
      'id' => 'standard',
      'base' => 'users',
      'field' => 'uid',
      'label' => t('author'),
    ),
    'filter' => array(
      'id' => 'user_name',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'field' => array(
      'id' => 'user',
    ),
  );

  $data['feeds_feed']['created'] = array(
    'title' => t('Created date'),
    'help' => t('The date the feed was created.'),
    'field' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'date'
    ),
    'filter' => array(
      'id' => 'date',
    ),
  );

  $data['feeds_feed']['changed'] = array(
    'title' => t('Updated date'),
    'help' => t('The date the feed was last updated.'),
    'field' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'date'
    ),
    'filter' => array(
      'id' => 'date',
    ),
  );

  $data['feeds_feed']['imported'] = array(
    'title' => t('Imported date'),
    'help' => t('The date the feed was last imported.'),
    'field' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'date'
    ),
    'filter' => array(
      'id' => 'date',
    ),
  );

  $data['feeds_feed']['status'] = array(
    'title' => t('Import status'),
    'help' => t('Whether or not the feed is active.'),
    'field' => array(
      'id' => 'boolean',
      'output formats' => array(
        'active-inactive' => array(t('Active'), t('Inactive')),
      ),
    ),
    'filter' => array(
      'id' => 'boolean',
      'label' => t('Active'),
      'type' => 'yes-no',
      // Use status = 1 instead of status <> 0 in WHERE statement.
      'use_equal' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['feeds_feed']['feeds_feed_bulk_form'] = array(
    'title' => t('Feed operations bulk form'),
    'help' => t('Add a form element that lets you run operations on multiple feeds.'),
    'field' => array(
      'id' => 'feeds_feed_bulk_form',
    ),
  );

  $data['feeds_feed']['view_feed'] = array(
    'field' => array(
      'title' => t('Link to feed'),
      'help' => t('Provide a simple link to the feed.'),
      'id' => 'feeds_feed_link',
    ),
  );

  $data['feeds_feed']['edit_feed'] = array(
    'field' => array(
      'title' => t('Link to edit feed'),
      'help' => t('Provide a simple link to edit the feed.'),
      'id' => 'feeds_feed_link_edit',
    ),
  );

  $data['feeds_feed']['delete_feed'] = array(
    'field' => array(
      'title' => t('Link to delete feed'),
      'help' => t('Provide a simple link to delete the feed.'),
      'id' => 'feeds_feed_link_delete',
    ),
  );

  $data['feeds_feed']['import_feed'] = array(
    'field' => array(
      'title' => t('Link to import feed'),
      'help' => t('Provide a simple link to import the feed.'),
      'id' => 'feeds_feed_link_import',
    ),
  );

  $data['feeds_feed']['clear_feed'] = array(
    'field' => array(
      'title' => t('Link to clear feed'),
      'help' => t('Provide a simple link to clear the feed.'),
      'id' => 'feeds_feed_link_clear',
    ),
  );

  return $data;
}

/**
 * Implements hook_field_views_data().
 *
 * @see field_views_field_default_views_data()
 */
function feeds_field_views_data(FieldStorageConfig $field) {
  $data = field_views_field_default_views_data($field);
  foreach ($data as $table_name => $table_data) {
    // Add the relationship only on the target_id field.
    $data[$table_name][$field->getName() . '_target_id']['relationship'] = array(
      'id' => 'standard',
      'base' => 'feeds_feed',
      'base field' => 'fid',
      'label' => t('feed from !field_name', array('!field_name' => $field->getName())),
    );
  }

  return $data;
}
