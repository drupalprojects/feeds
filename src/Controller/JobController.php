<?php

/**
 * @file
 * Contains \Drupal\feeds\Controller\JobController.
 */

namespace Drupal\feeds\Controller;

use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\State\StateInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles background taks for a feed.
 */
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
   * Constructs a JobController object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state object that holds our token.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock_backend
   *   The lock backend to use.
   */
  public function __construct(StateInterface $state, LockBackendInterface $lock_backend) {
    $this->state = $state;
    $this->lockBackend = $lock_backend;
  }

  /**
   * Executes a callback.
   *
   * @param \Drupal\feeds\FeedInterface $feeds_feed
   *   The Feed we are executing a job for.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object to grab POST params from.
   *
   * @todo Configure a time limit.
   * @todo Really awesome error handling.
   */
  public function execute(FeedInterface $feeds_feed, Request $request) {
    $cid = 'feeds_feed:' . $feeds_feed->id();

    if ($token = $request->request->get('token') && $job = $this->state->get($cid)) {
      if ($job['token'] == $token && $lock = $this->lockBackend->acquire($cid)) {
        $method = $job['method'];

        $this->state->delete($cid);

        ignore_user_abort(TRUE);
        set_time_limit(0);

        while ($feeds_feed->$method() != FeedStateInterface::BATCH_COMPLETE) {
          // Reset static caches in between runs to avoid memory leaks.
          drupal_reset_static();
        }

        $this->lockBackend->release($cid);
      }
    }
  }

}
