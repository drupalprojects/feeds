<?php

/**
 * @file
 * Contains \Drupal\feeds\PuSH\SubscriptionInterface.
 */

namespace Drupal\feeds\PuSH;

/**
 * Manages PuSH subscriptions for Feeds.
 */
interface SubscriptionInterface {

  /**
   * Sets a subscription.
   *
   * This takes the subscription by reference and will populate the following
   * keys automatically:
   * - expires: Will set based off the lease time.
   * - secret: If inserting, a new secret will be generated.
   * - token: If inserting, a new token will be generated. (deprecated)
   * - created: If inserted, the created time will be set.
   *
   * @param array &$data
   *   The subscription data in the form:
   *   - id: The Feed id.
   *   - topic: The URL of the subscription.
   *   - hub: The hub that the subscription is being managed by.
   *   - lease: The amount of time the subscription will last.
   *   - token: A token to be returned back. (deprecated)
   *
   * @return bool
   *   Returns true on insert and false on update.
   */
  public function setSubscription(array &$data);

  /**
   * Returns the subscription for the given Feed.
   *
   * @param int $key
   *   The Feed id.
   *
   * @return array|false
   *   Returns the subscription array, or false if it does not exist.
   */
  public function getSubscription($key);

  /**
   * Returns whether or not a subscription exists for a Feed.
   *
   * @param int $key
   *   The Feed id.
   *
   * @return bool
   *   True if the subscription exists, false if not.
   */
  public function hasSubscription($key);

  /**
   * Deletes a subscription.
   *
   * @param int $key
   *   The Feed id.
   *
   * @return bool
   *   Returns true if the subscription was actually deleted, and false if it
   *   did not exist.
   */
  public function deleteSubscription($key);

}
