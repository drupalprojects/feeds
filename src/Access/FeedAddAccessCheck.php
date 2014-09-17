<?php

/**
 * @file
 * Contains \Drupal\feeds\Access\FeedAddAccessCheck.
 */

namespace Drupal\feeds\Access;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Access check for feeds link add list routes.
 */
class FeedAddAccessCheck implements AccessInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a FeedAddAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    // @todo Perhaps read config directly rather than load all importers.
    $access_controller = $this->entityManager->getAccessController('feeds_feed');
    foreach ($this->entityManager->getStorage('feeds_importer')->loadEnabled() as $importer) {
      if ($access_controller->createAccess($importer->id(), $account)) {
        return self::ALLOW;
      }
    }

    return static::DENY;
  }

}
