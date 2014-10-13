<?php

/**
 * @file
 * Contains \Drupal\feeds\Controller\SubscriptionController.
 */

namespace Drupal\feeds\Controller;

use Drupal\feeds\SubscriptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for PuSH module routes.
 */
class SubscriptionController {

  /**
   * Handles subscribe/unsubscribe requests.
   *
   * @param \Drupal\feeds\SubscriptionInterface $feeds_subscription
   *   The subscription entity.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return Symfony\Component\HttpFoundation\Response
   *   The response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown if the subscription was not found.
   */
  public function subscribe(SubscriptionInterface $feeds_subscription, Request $request) {

    // Verify the request has the proper attributes.
    if ($request->query->get('hub.challenge') === NULL) {
      throw new NotFoundHttpException();
    }
    elseif ($request->query->get('hub.topic') !== $feeds_subscription->getTopic()) {
      throw new NotFoundHttpException();
    }
    elseif (!in_array($request->query->get('hub.mode'), ['subscribe', 'unsubscribe'])) {
      throw new NotFoundHttpException();
    }

    if ($lease_time = $request->query->get('hub.lease_seconds')) {
      $feeds_subscription->setLease($lease_time);
      $feeds_subscription->setExpire($lease_time + REQUEST_TIME);
    }

    $feeds_subscription->setState($request->query->get('hub.mode') . 'd');
    $feeds_subscription->save();

    return new Response($request->query->get('hub.challenge'), 200);
  }

  /**
   * Receives a notification.
   *
   * @param \Drupal\feeds\SubscriptionInterface $feeds_subscription
   *   The subscription entity.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return Symfony\Component\HttpFoundation\Response
   *   The response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown if the subscription was not found or the request parameters were
   *   invalid.
   */
  public function receive(SubscriptionInterface $feeds_subscription, Request $request) {
    if (!$sig = $request->headers->get('X-Hub-Signature')) {
      throw new NotFoundHttpException();
    }

    $result = array();
    parse_str($sig, $result);
    if (empty($result['sha1'])) {
      throw new NotFoundHttpException();
    }
    $raw = file_get_contents('php://input');

    if (!$feeds_subscription->checkSignature($result['sha1'], $raw)) {
      throw new NotFoundHttpException();
    }

    $feeds_feed->importRaw($raw);
    return new Response('', 200);
  }

}
