<?php

/**
 * @file
 * Contains \Drupal\feeds\Controller\SubscriptionController.
 */

namespace Drupal\feeds\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\feeds\Entity\Feed;
use Drupal\feeds\Entity\Subscription;
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
   * @param int $feeds_subscription_id
   *   The subscription entity id.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return Symfony\Component\HttpFoundation\Response
   *   The response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown if the subscription was not found, or if the request is invalid.
   */
  public function subscribe($feeds_subscription_id, Request $request) {
    // This is an invalid request.
    if ($request->query->get('hub_challenge') === NULL) {
      throw new NotFoundHttpException();
    }

    if ($request->query->get('hub_topic') === NULL) {
      throw new NotFoundHttpException();
    }

    // A subscribe request.
    if ($request->query->get('hub_mode') === 'subscribe') {
      return $this->handleSubscribe((int) $feeds_subscription_id, $request);
    }

    // An unsubscribe request.
    if ($request->query->get('hub_mode') === 'unsubscribe') {
      return $this->handleUnsubscribe((int) $feeds_subscription_id, $request);
    }

    // Whatever.
    throw new NotFoundHttpException();
  }

  /**
   * Handles a subscribe request.
   *
   * @param int $subscription_id
   *   The subscription entity id.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response challenge.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown if anything seems amiss.
   */
  protected function handleSubscribe($subscription_id, Request $request) {
    if (!$subscription = Subscription::load($subscription_id)) {
      throw new NotFoundHttpException();
    }

    if ($request->query->get('hub_topic') !== $subscription->getTopic()) {
      throw new NotFoundHttpException();
    }

    if ($subscription->getState() !== 'subscribing' && $subscription->getState() !== 'subscribed') {
      throw new NotFoundHttpException();
    }

    if ($lease_time = $request->query->get('hub_lease_seconds')) {
      $subscription->setLease($lease_time);
    }

    $subscription->setState('subscribed');
    $subscription->save();

    return new Response(SafeMarkup::checkPlain($request->query->get('hub_challenge')), 200);
  }

  /**
   * Handles an unsubscribe request.
   *
   * @param int $subscription_id
   *   The subscription entity id.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response challenge.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown if anything seems amiss.
   */
  protected function handleUnsubscribe($subscription_id, Request $request) {
    // The subscription id already deleted, but waiting in the keyvalue store.
    $id = sha1($request->query->get('hub_topic')) . ':' . $subscription_id;
    $subscription = \Drupal::keyValueExpirable('feeds_push_unsubscribe')->get($id);

    if (!$subscription) {
      throw new NotFoundHttpException();
    }

    \Drupal::keyValueExpirable('feeds_push_unsubscribe')->delete($id);

    return new Response(SafeMarkup::checkPlain($request->query->get('hub_challenge')), 200);
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
   *   Thrown if anything seems amiss.
   */
  public function receive(SubscriptionInterface $feeds_subscription, Request $request) {
    if (!$sig = $request->headers->get('X-Hub-Signature')) {
      throw new NotFoundHttpException();
    }

    // X-Hub-Signature is in the format sha1=signature.
    $result = [];
    parse_str($sig, $result);
    if (empty($result['sha1'])) {
      throw new NotFoundHttpException();
    }

    $raw = file_get_contents('php://input');

    if (!$feeds_subscription->checkSignature($result['sha1'], $raw)) {
      throw new NotFoundHttpException();
    }

    $feed = Feed::load($feeds_subscription->id());

    try {
      $feed->pushImport($raw);
      return new Response('', 200);
    }
    catch (\Exception $e) {
      return new Response('', 500);
    }
  }

}
