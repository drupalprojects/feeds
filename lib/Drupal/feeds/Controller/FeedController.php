<?php

/**
 * @file
 * Contains \Drupal\feeds\Controller\FeedController.
 */

namespace Drupal\feeds\Controller;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Core\Entity\Feed;
use Drupal\feeds\Plugin\Core\Entity\Importer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for feed routes.
 */
class FeedController implements ControllerInterface {

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
  protected $feedStorage;

  /**
   * The importer storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $importerStorage;

  /**
   * Constructs a \Drupal\feeds\Controller\FeedController object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $entity_manager
   *   The Entity manager.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $feed_storage
   *   The feed storage controller.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $importer_storage
   *   The feed importer controller.
   */
  public function __construct(PluginManagerInterface $entity_manager, EntityStorageControllerInterface $feed_storage, EntityStorageControllerInterface $importer_storage) {
    $this->entityManager = $entity_manager;
    $this->feedStorage = $feed_storage;
    $this->importerStorage = $importer_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('plugin.manager.entity');
    return new static(
      $entity_manager,
      $entity_manager->getStorageController('feeds_feed'),
      $entity_manager->getStorageController('feeds_importer')
    );
  }

  /**
   * Presents the feeds feed creation form.
   *
   * @return array
   *   A form array as expected by drupal_render().
   *
   * @todo Return a render array/twig template?
   */
  public function add(Request $request) {
    // Show add form if there is only one importer.
    $importers = $this->importerStorage->loadMultiple();
    if ($importers && count($importers) == 1) {
      $importer = reset($importers);
      return $this->addForm($importer, $request);
    }

    $rows = array();
    foreach ($importers as $importer) {
      if ($importer->disabled) {
        continue;
      }
      if (!(user_access('create ' . $importer->id() . ' feeds') || user_access('administer feeds'))) {
        continue;
      }
      $link = 'feed/add/' . $importer->id();
      $title = $importer->label();
      $rows[] = array(
        l($title, $link),
        check_plain($importer->description),
      );
    }
    if (!$rows) {
      drupal_set_message(t('There are no importers, go to <a href="@importers">Feed importers</a> to create one or enable an existing one.', array('@importers' => url('admin/structure/feeds'))));
    }
    $header = array(
      t('Import'),
      t('Description'),
    );
    return theme('table', array('header' => $header, 'rows' => $rows));
  }

  /**
   * Presents the feed creation form.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function addForm(Importer $feeds_importer, Request $request) {
    $feed = $this->feedStorage->create(array(
      'uid' => $GLOBALS['user']->uid,
      'importer' => $feeds_importer->id(),
      'status' => 1,
      'created' => REQUEST_TIME,
    ));

    return $this->entityManager->getForm($feed, 'add');
  }

  /**
   * Presents the feed.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function view(Feed $feeds_feed, Request $request) {
    return $this->entityManager
      ->getRenderController('feeds_feed')
      ->view($feeds_feed);
  }

}
