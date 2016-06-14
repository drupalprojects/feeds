<?php

namespace Drupal\feeds\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\feeds\Event\EventDispatcherTrait;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\FeedInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Base class for Feed queue workers.
 */
abstract class FeedQueueWorkerBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {
  use EventDispatcherTrait;

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Constructs a FeedQueueWorkerBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, QueueFactory $queue_factory, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->queueFactory = $queue_factory;
    $this->setEventDispatcher($event_dispatcher);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('queue'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Handles an import exception.
   */
  protected function handleException(FeedInterface $feed, \Exception $exception) {
    $feed->finishImport();

    if (!$exception instanceof EmptyFeedException) {
      throw $exception;
    }
  }

}
