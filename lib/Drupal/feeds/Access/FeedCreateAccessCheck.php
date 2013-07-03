<?php

/**
 * @file
 * Contains \Drupal\feeds\Access\FeedCreateAccessCheck.
 */

namespace Drupal\feeds\Access;

use Drupal\Core\Entity\EntityCreateAccessCheck;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides an access check for feed creation.
 */
class FeedCreateAccessCheck extends EntityCreateAccessCheck {

  /**
   * {@inheritdoc}
   */
  protected $requirementsKey = '_access_feeds_feed_create';

  /**
   * {@inheritdoc}
   */
  protected function prepareEntityValues(array $definition, Request $request, $bundle = NULL) {
    $values = array();
    if ($importer = $request->attributes->get('feeds_importer')) {
      $values = parent::prepareEntityValues($definition, $request, $importer->id());
    }
    return $values;
  }

}
