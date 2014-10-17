<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\QueueWorker\PushUnsubscribe.
 */

namespace Drupal\feeds\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\feeds\SubscriptionInterface;

/**
 * @QueueWorker(
 *   id = "feeds_push_unsubscribe",
 *   title = @Translation("PubSubHubbub unsubscribe")
 * )
 */
class PushUnsubscribe extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($subscription) {
    if (!$subscription instanceof SubscriptionInterface) {
      return;
    }

    if (!$subscription->getHub()) {
      return;
    }

    $callback = $this->url('feeds.subscribe', ['feeds_feed' => $subscription->id()], ['absolute' => TRUE]);

    $post_body = [
      'hub.callback' => $callback,
      'hub.mode' => 'unsubscribe',
      'hub.topic' => $subscription->getTopic(),
      'hub.secret' => $subscription->getSecret(),
    ];

    try {
      $response = $this->client->post($subscription->getHub(), ['body' => $post_body]);
    }
    catch (RequestException $e) {
      $this->loggerFactory->get('feeds')->warning('%error', ['%error' => $e->getMessage()]);
    }
  }

}
