<?php

/**
 * @file
 * Contains \Drupal\feeds\Access\FeedAddAccessCheck.
 */

namespace Drupal\feeds\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\feeds\ImporterInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Access check for feeds link create list routes.
 */
class FeedAddAccessCheck implements AccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return array_key_exists('_access_feeds_feed_add', $route->getRequirements());
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    if (user_access('administer feeds')) {
      return TRUE;
    }

    // If there is valid entity of the given entity type, check its access.
    if ($request->attributes->has('feeds_importer')) {
      $importer = $request->attributes->get('feeds_importer');
      if ($importer instanceof ImporterInterface) {
        return user_access("add {$importer->id()} feeds");
      }
    }
  }

}
