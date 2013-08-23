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
   * Cache for access checks.
   *
   * This is only here since access() gets called 5 times per page. Hopefully
   * that will go away.
   *
   * @var array
   */
  protected $access = array();

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
  public function appliesTo() {
    return array('_access_feeds_feed_create_list');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    $account = $request->attributes->get('_account');

    // @todo Remove this when user service is added.
    if (!$account) {
      $account = $GLOBALS['user'];
    }

    // @todo Revisit this cache to see if it's necessary.
    if (isset($this->access[$account->id()])) {
      return $this->access[$account->id()];
    }

    $this->access[$account->id()] = self::DENY;

    if ($account->hasPermission('administer feeds')) {
      $this->access[$account->id()] = self::ALLOW;
    }

    // @todo Perhaps read config directly rather than load all importers.
    foreach ($this->importerStorage->loadEnabled() as $importer) {
      if ($account->hasPermission("create {$importer->id()} feeds")) {
        $this->access[$account->id()] = self::ALLOW;
        break;
      }
    }

    return $this->access[$account->id()];
  }

}
