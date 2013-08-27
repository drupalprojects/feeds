<?php

/**
 * @file Contains \Drupal\feeds\Controller\JobController.
 */

namespace Drupal\feeds\Controller;

use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\feeds\FeedInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class JobController {

  /**
   * The state object to use.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $state;

  /**
   * The lock backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lockBackend;

  /**
   * The container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Constructs a JobController object.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreInterface $state
   *   The state object that holds our token.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock_backend
   *   The lock backend to use.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container, so that we can get the correct service.
   */
  public function __construct(KeyValueStoreInterface $state, LockBackendInterface $lock_backend, ContainerInterface $container) {
    $this->state = $state;
    $this->lockBackend = $lock_backend;
    $this->container = $container;
  }

  /**
   * Executes a callback.
   *
   * @param \Drupal\feeds\FeedInterface $feeds_feed
   *   The Feed we are executing a job for.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object to grab POST params from.
   */
  public function execute(FeedInterface $feeds_feed, Request $request) {
    $cid = 'feeds_feed:' . $feeds_feed->id();

    if ($token = $request->request->get('token') && $job = $this->state->get($cid)) {
      if ($job['token'] == $token && $lock = $this->lockBackend->acquire($cid)) {
        ignore_user_abort(TRUE);
        set_time_limit(0);
        $this->container->get($job['service'])->execute($feeds_feed);
        $this->state->delete($cid);
        $this->lockBackend->release($cid);
      }
    }
  }

}
