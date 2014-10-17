<?php

/**
 * @file
 * Contains \Drupal\feeds\EventSubscriber\PubSubHubbub.
 */

namespace Drupal\feeds\EventSubscriber;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\feeds\Entity\Subscription;
use Drupal\feeds\Event\DeleteFeedsEvent;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\FetchEvent;
use Drupal\feeds\Result\HttpFetcherResultInterface;
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
   * The subscription storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Constructs a PubSubHubbub object.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue service.
   */
  public function __construct(QueueFactory $queue_factory, EntityManagerInterface $entity_manager) {
    $this->queueFactory = $queue_factory;
    $this->storage = $entity_manager->getStorage('feeds_subscription');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[FeedsEvents::FETCH][] = ['onPostFetch', FeedsEvents::AFTER];
    $events[FeedsEvents::FEEDS_DELETE][] = 'onDeleteMultipleFeeds';
    return $events;
  }

  /**
   * Subscribes to a feed.
   */
  public function onPostFetch(FetchEvent $event) {
    $feed = $event->getFeed();
    $fetcher = $feed->getImporter()->getFetcher();

    $subscription = $this->storage->load($feed->id());

    if (!$fetcher->getConfiguration('use_pubsubhubbub')) {
      if ($subscription) {
        $subscription->unsubscribe();
      }
      return;
    }

    if (!$hub = $this->findHub($event->getFetcherResult())) {
      $hub = $fetcher->getConfiguration('fallback_hub');
    }

    if (!$hub) {
      if ($subscription) {
        // The hub is gone and there's no fallback.
        $subscription->unsubscribe();
      }
      return;
    }

    // Subscription does not exist yet.
    if (!$subscription) {
      $this->storage->create([
        'fid' => $feed->id(),
        'topic' => $feed->getSource(),
        'hub' => $hub,
      ])->subscribe();
    }
    elseif ($feed->getSource() !== $subscription->getTopic() || $subscription->getHub() !== $hub) {
      $subscription->unsubscribe();

      $this->storage->create([
        'fid' => $feed->id(),
        'topic' => $feed->getSource(),
        'hub' => $hub,
      ])->subscribe();
    }
  }

  /**
   * Finds a hub from a fetcher result.
   *
   * @param \Drupal\feeds\Result\FetcherResultInterface $fetcher_result
   *   The fetcher result.
   *
   * @return string|null
   *   The hub URL or null if one wasn't found.
   */
  protected function findHub(FetcherResultInterface $fetcher_result) {
    if ($fetcher_result instanceof HttpFetcherResultInterface) {
      if ($hub = HttpHelpers::findLinkHeader($fetcher_result->getHeaders(), 'hub')) {
        return $hub;
      }
    }

    return HttpHelpers::findHubFromXml($fetcher_result->getRaw());
  }

  /**
   * Deletes subscriptions when feeds are deleted.
   */
  public function onDeleteMultipleFeeds(DeleteFeedsEvent $event) {
    $subscriptions = $this->storage->loadMultiple(array_keys($event->getFeeds()));

    foreach ($subscriptions as $subscription) {
      $subscription->unsubscribe();
    }
  }

}
