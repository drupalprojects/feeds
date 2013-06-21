<?php

/**
 * @file
 * Contains \Drupal\feeds\Access\FeedAddListAccessCheck.
 */

namespace Drupal\feeds\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Access check for feeds link add list routes.
 */
class FeedAddListAccessCheck implements AccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return array_key_exists('_access_feeds_feed_add_list', $route->getRequirements());
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    if (user_access('administer feeds')) {
      return TRUE;
    }

    foreach (entity_load_multiple('feeds_importer') as $importer) {
      if (user_access("add {$importer->id()} feeds")) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
