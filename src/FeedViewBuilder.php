<?php

/**
 * @file
 * Contains \Drupal\feeds\FeedViewBuilder.
 */

namespace Drupal\feeds;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Render controller for feeds feed items.
 */
class FeedViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  // protected function getBuildDefaults(EntityInterface $entity, $view_mode, $langcode) {
  //   $defaults = parent::getBuildDefaults($entity, $view_mode, $langcode);
  //   // $defaults['#theme'] = 'feeds_feed_source';
  //   return $defaults;
  // }

}
