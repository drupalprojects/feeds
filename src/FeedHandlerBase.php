<?php

/**
 * @file
 * Contains \Drupal\feeds\FeedHandlerBase.
 */

namespace Drupal\feeds;

use Drupal\Core\Entity\EntityHandlerBase;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\feeds\Event\EventDispatcherTrait;
use Drupal\feeds\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a base class for entity handlers.
 */
abstract class FeedHandlerBase extends EntityHandlerBase implements EntityHandlerInterface {
  use EventDispatcherTrait;

  /**
   * Constructs a FeedHandlerBase object.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher) {
    $this->setEventDispatcher($event_dispatcher);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('event_dispatcher')
    );
  }

}
