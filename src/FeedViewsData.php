<?php

/**
 * @file
 * Contains \Drupal\feeds\FeedViewsData.
 */

namespace Drupal\feeds;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the feed entity type.
 */
class FeedViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

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

}
