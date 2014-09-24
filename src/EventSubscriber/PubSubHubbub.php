<?php

namespace Drupal\feeds\EventSubscriber;

use Drupal\Core\Queue\QueueFactory;
use Drupal\feeds\Event\DeleteFeedsEvent;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\FetcherEvent;
use Drupal\feeds\PuSH\SubscriptionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event listener for PubSubHubbub subscriptions.
 */
class PubSubHubbub implements EventSubscriberInterface {

  /**
   * The queue service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The subscription controller.
   *
   * @var \Drupal\feeds\PuSH\SubscriptionInterface
   */
  protected $subscription;

  /**
   * Constructs a PubSubHubbub object.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue service.
   * @param \Drupal\feeds\PuSH\SubscriptionInterface $subscription
   */
  public function __construct(QueueFactory $queue_factory, SubscriptionInterface $subscription) {
    $this->queueFactory = $queue_factory;
    $this->subscription = $subscription;
      // $container->get('queue')->get('feeds_push_unsubscribe'),
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = array();
    $events[FeedsEvents::POST_FETCH][] = 'onPostFetch';
    $events[FeedsEvents::FEEDS_DELETE][] = 'onDeleteMultipleFeeds';
    return $events;
  }

  /**
   * Subscribes to a feed.
   */
  public function onPostFetch(FetcherEvent $event) {
    $feed = $event->getFeed();
    $fetcher = $feed->getImporter()->getFetcher();
    $fetcher_result = $event->getFetcherResult();

    if (!$fetcher->getConfiguration('use_pubsubhubbub')) {
      return;
    }

    // Subscription does not exist yet.
    if (!$subscription = $this->subscription->getSubscription($feed->id())) {
      $sub = array(
        'id' => $feed->id(),
        'state' => 'unsubscribed',
        'hub' => '',
        'topic' => $feed->getSource(),
      );
      $this->subscription->setSubscription($sub);
      // Subscribe to new topic.
      $this->queueFactory->get('feeds_push_subscribe')->createItem($feed->id());
    }

    // Source has changed.
    elseif ($subscription['topic'] !== $feed->getSource()) {
      // Subscribe to new topic.
      $this->queueFactory->get('feeds_push_subscribe')->createItem($feed->id());
      // Unsubscribe from old topic.
      $this->queueFactory->get('feeds_push_unsubscribe')->createItem($feed->id());
      // Save new topic to subscription.
      $subscription['topic'] = $feed->getSource();
      $this->subscription->setSubscription($subscription);
    }

    // Hub exists, but we aren't subscribed.
    // @todo Is this the best way to handle this?
    // @todo Periodically check for new hubs... Always check for new hubs...
    // Maintain a retry count so that we don't keep trying indefinitely.
    elseif ($subscription['hub']) {
      switch ($subscription['state']) {
        // Don't do anything if we are in the process of subscribing.
        case 'subscribe':
        case 'subscribed':
          break;

        default:
          $this->queueFactory->get('feeds_push_subscribe')->createItem($feed->id());
          break;
      }
    }
  }

  public function onDeleteMultipleFeeds(DeleteFeedsEvent $event) {
    $fids = array_keys($event->getFeeds());
    foreach ($this->subscription->hasSubscriptions($fids) as $fid) {
      // Add to unsubscribe queue.
      $this->queueFactory->get('feeds_push_unsubscribe')->createItem(array(
        'id' => $fid,
      ));
    }
  }

}
