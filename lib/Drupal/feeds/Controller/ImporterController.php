<?php

/**
 * @file
 * Contains \Drupal\feeds\Controller\ImporterController.
 */

namespace Drupal\feeds\Controller;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Entity\Feed;
use Drupal\feeds\Entity\Importer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for feed routes.
 */
class ImporterController implements ControllerInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $entityManager;

  /**
   * The feed storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $importerStorage;

  /**
   * Constructs a \Drupal\feeds\Controller\ImporterController object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $entity_manager
   *   The Entity manager.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $importer_storage
   *   The feed importer controller.
   */
  public function __construct(PluginManagerInterface $entity_manager, EntityStorageControllerInterface $importer_storage) {
    $this->entityManager = $entity_manager;
    $this->importerStorage = $importer_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('plugin.manager.entity');
    return new static(
      $entity_manager,
      $entity_manager->getStorageController('feeds_importer')
    );
  }

  /**
   * Presents the feed creation form.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function createForm(Request $request) {
    $importer = $this->importerStorage->create(array());

    return $this->entityManager->getForm($importer, 'edit');
  }

}
