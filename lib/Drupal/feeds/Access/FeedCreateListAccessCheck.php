<?php

/**
 * @file
 * Contains \Drupal\feeds\Access\FeedCreateListAccessCheck.
 */

namespace Drupal\feeds\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Entity\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Access check for feeds link add list routes.
 */
class FeedCreateListAccessCheck implements AccessCheckInterface {

  /**
   * The importer storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $importerStorage;

  /**
   * Constructs a FeedCreateListAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The Entity manager.
   */
  public function __construct(EntityManager $entity_manager) {
    $this->importerStorage = $entity_manager->getStorageController('feeds_importer');
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return array_key_exists('_access_feeds_feed_create_list', $route->getRequirements());
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    // @todo Perhaps read config directly rather than load all importers.
    foreach ($this->importerStorage->loadMultiple() as $importer) {
      if (user_access("create {$importer->id()} feeds")) {
        return self::ALLOW;
      }

    }

    return self::DENY;
  }

}
