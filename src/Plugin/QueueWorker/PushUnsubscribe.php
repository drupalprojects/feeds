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


  }

}
