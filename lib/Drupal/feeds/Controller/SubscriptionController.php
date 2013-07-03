<?php

/**
 * @file
 * Contains \Drupal\feeds\Controller\SubscriptionController.
 */

namespace Drupal\feeds\Controller;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\PuSH\Subscription;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for feeds module routes.
 */
class SubscriptionController {

  /**
   * The subscription storage controller.
   *
   * @var \Drupal\feeds\PuSH\Subscription
   */
  protected $subscriptionCrud;

  /**
   * Constructs a SubscriptionController object.
   *
   * @param \Drupal\feeds\PuSH\Subscription $subscription_crud
   *   The subscription controller.
   */
  public function __construct(Subscription $subscription_crud) {
    $this->subscriptionCrud = $subscription_crud;
  }

  /**
   * Handles subscribe/unsubscribe requests.
   *
   * @param \Drupal\feeds\FeedInterface $feeds_feed
   *   The feed to perform the request on.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return Symfony\Component\HttpFoundation\Response
   *   The response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown if the subscription was not found.
   *
   * @todo Verify query settings in an access controller?
   */
  public function subscribe(FeedInterface $feeds_feed, Request $request) {

    // Verify the request has the proper attributes.
    if ($request->query->get('hub_challenge') === NULL) {
      throw new NotFoundHttpException();
    }
    elseif (!$sub = $this->subscriptionCrud->getSubscription($feeds_feed->id())) {
      throw new NotFoundHttpException();
    }
    elseif ($request->query->get('hub_topic') != $sub['topic']) {
      throw new NotFoundHttpException();
    }
    elseif ($request->query->get('hub_verify_token') != $sub['token']) {
      throw new NotFoundHttpException();
    }
    elseif (!in_array($request->query->get('hub_mode'), array('subscribe', 'unsubscribe'))) {
      throw new NotFoundHttpException();
    }

    if ($lease_time = $request->query->get('hub_lease_seconds')) {
      $sub['lease'] = $lease_time;
    }

    $sub['state'] = $request->query->get('hub_mode') . 'd';

    $this->subscriptionCrud->setSubscription($sub);

    return new Response($request->query->get('hub_challenge'), 200);
  }

  /**
   * Receives a notification.
   *
   * @param \Drupal\feeds\FeedInterface $feeds_feed
   *   The feed entity to perform the request on.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return
   *   An XML string that is the payload of the notification if valid, FALSE
   *   otherwise.
   */
  public function receive(FeedInterface $feeds_feed, Request $request) {
    if (!$sig = $request->headers->get('X-Hub-Signature')) {
      throw new NotFoundHttpException();
    }

    $result = array();
    parse_str($sig, $result);
    if (empty($result['sha1'])) {
      throw new NotFoundHttpException();
    }

    if (!$sub = $this->subscriptionCrud->getSubscription($feeds_feed->id())) {
      throw new NotFoundHttpException();
    }

    $raw = file_get_contents('php://input');

    if ($result['sha1'] !== hash_hmac('sha1', $raw, $sub['secret'])) {
      throw new NotFoundHttpException();
    }

    watchdog('feeds', $raw);

    $feeds_feed->importRaw($raw);

    return new Response('', 200);
  }

}
