<?php

/**
 * @file
 * Contains \Drupal\feeds\Access\FeedCreateListAccessCheck.
 */

namespace Drupal\feeds\Access;

use Drupal\Core\Access\StaticAccessCheckInterface;
use Drupal\Core\Entity\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Access check for feeds link add list routes.
 */
class FeedCreateListAccessCheck implements StaticAccessCheckInterface {

  /**
   * The importer storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $importerStorage;

  /**
   * The feed access controller.
   *
   * @var \Drupal\Core\Entity\EntityAccessControllerInterface
   */
  protected $feedAccessController;

  /**
   * Constructs a FeedCreateListAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The Entity manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in user.
   */
  public function __construct(EntityManager $entity_manager) {
    $this->importerStorage = $entity_manager->getStorageController('feeds_importer');
    $this->feedAccessController = $entity_manager->getAccessController('feeds_feed');
  }

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return array('_access_feeds_feed_create_list');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    // @todo Perhaps read config directly rather than load all importers.
    foreach ($this->importerStorage->loadEnabled() as $importer) {
      if ($this->feedAccessController->createAccess($importer->id())) {
        return self::ALLOW;
      }
    }
  }

}
