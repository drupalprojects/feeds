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
  $data['feeds_feed']['table']['base'] = [
    'field' => 'fid',
    'title' => t('Feeds'),
    'weight' => -10,
    // 'access query tag' => 'node_access',
    'defaults' => [
      'field' => 'title',
    ],
  ];
  $data['feeds_feed']['table']['entity type'] = 'feeds_feed';
  // $data['node']['table']['wizard_id'] = 'node';

  $data['feeds_feed']['fid'] = [
    'title' => t('Fid'),
    'help' => t('The feed ID.'),
    'field' => [
      'id' => 'standard',
    ],
    'argument' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['feeds_feed']['title'] = [
    'title' => t('Title'),
    'help' => t('The feed title.'),
    'field' => [
      'id' => 'feeds_feed',
      'link_to_feed default' => TRUE,
     ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  $data['feeds_feed']['type'] = [
    'title' => t('Feed type'),
    'help' => t('The feed type.'),
    'field' => [
      'id' => 'feeds_feed_type',
     ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'bundle',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  $data['feeds_feed']['uid'] = [
    'title' => t('Author uid'),
    'help' => t('The user authoring the feed. If you need more fields than the uid add the feed: author relationship'),
    'relationship' => [
      'title' => t('Feed author'),
      'help' => t('Relate feed to the user who created it.'),
      'id' => 'standard',
      'base' => 'users',
      'field' => 'uid',
      'label' => t('author'),
    ],
    'filter' => [
      'id' => 'user_name',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
    'field' => [
      'id' => 'user',
    ],
  ];

  $data['feeds_feed']['created'] = [
    'title' => t('Created date'),
    'help' => t('The date the feed was created.'),
    'field' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'date'
    ],
    'filter' => [
      'id' => 'date',
    ],
  ];

  $data['feeds_feed']['changed'] = [
    'title' => t('Updated date'),
    'help' => t('The date the feed was last updated.'),
    'field' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'date'
    ],
    'filter' => [
      'id' => 'date',
    ],
  ];

  $data['feeds_feed']['imported'] = [
    'title' => t('Imported date'),
    'help' => t('The date the feed was last imported.'),
    'field' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'date'
    ],
    'filter' => [
      'id' => 'date',
    ],
  ];

  $data['feeds_feed']['status'] = [
    'title' => t('Import status'),
    'help' => t('Whether or not the feed is active.'),
    'field' => [
      'id' => 'boolean',
      'output formats' => [
        'active-inactive' => [t('Active'), t('Inactive')],
      ],
    ],
    'filter' => [
      'id' => 'boolean',
      'label' => t('Active'),
      'type' => 'yes-no',
      // Use status = 1 instead of status <> 0 in WHERE statement.
      'use_equal' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['feeds_feed']['feeds_feed_bulk_form'] = [
    'title' => t('Feed operations bulk form'),
    'help' => t('Add a form element that lets you run operations on multiple feeds.'),
    'field' => [
      'id' => 'feeds_feed_bulk_form',
    ],
  ];

  $data['feeds_feed']['view_feed'] = [
    'field' => [
      'title' => t('Link to feed'),
      'help' => t('Provide a simple link to the feed.'),
      'id' => 'feeds_feed_link',
    ],
  ];

  $data['feeds_feed']['edit_feed'] = [
    'field' => [
      'title' => t('Link to edit feed'),
      'help' => t('Provide a simple link to edit the feed.'),
      'id' => 'feeds_feed_link_edit',
    ],
  ];

  $data['feeds_feed']['delete_feed'] = [
    'field' => [
      'title' => t('Link to delete feed'),
      'help' => t('Provide a simple link to delete the feed.'),
      'id' => 'feeds_feed_link_delete',
    ],
  ];

  $data['feeds_feed']['import_feed'] = [
    'field' => [
      'title' => t('Link to import feed'),
      'help' => t('Provide a simple link to import the feed.'),
      'id' => 'feeds_feed_link_import',
    ],
  ];

  $data['feeds_feed']['clear_feed'] = [
    'field' => [
      'title' => t('Link to clear feed'),
      'help' => t('Provide a simple link to clear the feed.'),
      'id' => 'feeds_feed_link_clear',
    ],
  ];

  return $data;
}

/**
 * Implements hook_field_views_data().
 *
 * @see views_field_default_views_data()
 */
function feeds_field_views_data(FieldStorageConfig $field) {
  $data = views_field_default_views_data($field);
  foreach ($data as $table_name => $table_data) {
    // Add the relationship only on the target_id field.
    $data[$table_name][$field->getName() . '_target_id']['relationship'] = [
      'id' => 'standard',
      'base' => 'feeds_feed',
      'base field' => 'fid',
      'label' => t('feed from !field_name', ['!field_name' => $field->getName()]),
    ];
  }

  return $data;
}
