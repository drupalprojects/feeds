<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\Derivative\FeedQueueWorker.
 */

namespace Drupal\feeds\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides separate queue works for importers.
 *
 * @see \Drupal\feeds\Plugin\QueueWorker\FeedRefresh
 */
class FeedQueueWorker extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Constructs an FeedQueueWorker object.
   *
   * @param string $base_plugin_id
   *   The base plugin id.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity manager.
   */
  public function __construct(EntityStorageInterface $storage) {
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static($container->get('entity.manager')->getStorage('feeds_importer'));
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $derivatives = [];
    foreach ($this->storage->loadMultiple() as $importer) {
      $derivatives[$importer->id()] = $base_plugin_definition;
    }

    return $derivatives;
  }

}
