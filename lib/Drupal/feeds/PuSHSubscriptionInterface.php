<?php

namespace Drupal\feeds;

/**
 * Implement to provide a storage backend for subscriptions.
 *
 * Variables passed in to the constructor must be accessible as public class
 * variables.
 */
interface PuSHSubscriptionInterface {
  /**
   * @param $domain
   *   A string that defines the domain in which the subscriber_id is unique.
   * @param $subscriber_id
   *   A unique numeric subscriber id.
   * @param $hub
   *   The URL of the hub endpoint.
   * @param $topic
   *   The topic to subscribe to.
   * @param $secret
   *   A secret key used for message authentication.
   * @param $status
   *   The status of the subscription.
   *   'subscribe' - subscribing to a feed.
   *   'unsubscribe' - unsubscribing from a feed.
   *   'subscribed' - subscribed.
   *   'unsubscribed' - unsubscribed.
   *   'subscribe failed' - subscribe request failed.
   *   'unsubscribe failed' - unsubscribe request failed.
   * @param $post_fields
   *   An array of the fields posted to the hub.
   */
  public function __construct($domain, $subscriber_id, $hub, $topic, $secret, $status = '', $post_fields = '');

  /**
   * Save a subscription.
   */
  public function save();

  /**
   * Load a subscription.
   *
   * @return
   *   A PuSHSubscriptionInterface object if a subscription exist, NULL
   *   otherwise.
   */
  public static function load($domain, $subscriber_id);

  /**
   * Delete a subscription.
   */
  public function delete();
}
