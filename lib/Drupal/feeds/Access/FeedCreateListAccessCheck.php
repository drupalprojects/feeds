<?php

/**
 * @file
 * Contains \Drupal\feeds\Access\FeedCreateListAccessCheck.
 */

namespace Drupal\feeds\Access;

use Drupal\Core\Entity\EntityCreateAccessCheck;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Access check for feeds link add list routes.
 */
class FeedCreateListAccessCheck extends EntityCreateAccessCheck {

  /**
   * {@inheritdoc}
   */
  protected $requirementsKey = '_feeds_feed_add_access';

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    // @todo Perhaps read config directly rather than load all importers.
    $access_controller = $this->entityManager->getAccessController('feeds_feed');

    foreach ($this->entityManager->getStorageController('feeds_importer')->loadEnabled() as $importer) {
      if ($access_controller->createAccess($importer->id(), $account)) {
        return self::ALLOW;
      }
    }

    return static::DENY;
  }

}
