<?php

/**
 * @file
 * Contains \Drupal\feeds\FeedHandlerBase.
 */

namespace Drupal\feeds;

use Drupal\Component\Utility\String;
use Drupal\Core\Entity\EntityHandlerBase;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\feeds\Event\EventDispatcherTrait;
use Drupal\feeds\Exception\LockException;
use Drupal\feeds\FeedInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a base class for entity handlers.
 */
abstract class FeedHandlerBase extends EntityHandlerBase implements EntityHandlerInterface {
  use EventDispatcherTrait;

  /**
   * The lock backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * Constructs a FeedHandlerBase object.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher, LockBackendInterface $lock) {
    $this->eventDispatcher = $event_dispatcher;
    $this->lock = $lock;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static($container->get('event_dispatcher'), $container->get('lock'));
  }

  /**
   * Acquires a lock for this feed.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to acquire the lock for.
   *
   * @throws \Drupal\feeds\Exception\LockException
   *   If a lock for the requested job could not be acquired.
   */
  protected function acquireLock(FeedInterface $feed) {
    if (!$this->lock->acquire("feeds_feed_{$feed->id()}", 60.0)) {
      throw new LockException(String::format('Cannot acquire lock for feed @id / @fid.', array('@id' => $feed->bundle(), '@fid' => $feed->id())));
    }
  }

  /**
   * Releases a lock for this source.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to release the lock for.
   */
  protected function releaseLock(FeedInterface $feed) {
    $this->lock->release("feeds_feed_{$feed->id()}");
  }

}
