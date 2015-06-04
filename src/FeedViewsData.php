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
        'field' => 'fid',
      ),
    );

    return $data;
  }

}
