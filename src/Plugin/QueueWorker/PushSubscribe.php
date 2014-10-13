<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\QueueWorker\PushSubscribe.
 */

namespace Drupal\feeds\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\Url;
use Drupal\feeds\Component\HttpHelpers;
use Drupal\feeds\SubscriptionInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @QueueWorker(
 *   id = "feeds_push_subscribe",
 *   title = @Translation("PubSubHubbub subscribe")
 * )
 */
class PushSubscribe extends QueueWorkerBase implements ContainerFactoryPluginInterface {
  use UrlGeneratorTrait;

  protected $client;

  /**
   * Constructs a PushSubscribe object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ClientInterface $client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($subscription) {
    if (!$subscription instanceof SubscriptionInterface) {
      return;
    }

    if (!$subscription->getHub() && $hub = $this->findHub($subscription)) {
      $subscription->setHub($hub);
    }

    if (!$subscription->getHub()) {
      return;
    }

    $subscription->setState('subscribing');
    $subscription->save();

    $callback = $this->url('feeds.subscribe', ['feeds_feed' => $subscription->id()], ['absolute' => TRUE]);

    $post_body = [
      'hub.callback' => $callback,
      'hub.mode' => 'subscribe',
      'hub.topic' => $subscription->getTopic(),
      'hub.secret' => $subscription->getSecret(),
    ];

    try {
      $response = $this->client->post($subscription->getHub(), ['body' => $post_body]);
    }
    catch (RequestException $e) {
      watchdog('feeds', '%error', ['%error' => $e->getMessage()], WATCHDOG_WARNING);
      return;
    }

    // Response failed. Deleting the subscription will make it re-subscribe on
    // the next fetch.
    if ($response->getStatusCode() != 202) {
      $subscription->delete();
    }
  }

  /**
   * Finds a hub from a subscription.
   *
   * @param SubscriptionInterface $subscription
   *   The subscription.
   *
   * @return string|null
   *   The hub URL or null if one wasn't found.
   *
   * @todo Log/retry when downloading fails.
   */
  protected function findHub(SubscriptionInterface $subscription) {
    try {
      $response = $this->client->get($subscription->getTopic());
    }
    catch (RequestException $e) {
      return NULL;
    }

    if ($hub = HttpHelpers::findLinkHeader($response->getHeaders(), 'hub')) {
      return $hub;
    }

    return HttpHelpers::findHubFromXml((string) $response->getBody());
  }

}
