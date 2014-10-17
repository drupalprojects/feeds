<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\QueueWorker\PushSubscribe.
 */

namespace Drupal\feeds\Plugin\QueueWorker;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
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

  /**
   * The Guzzle client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a PushSubscribe object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \GuzzleHttp\ClientInterface $client
   *   The Guzzle client.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ClientInterface $client, LoggerChannelFactoryInterface $logger_factory) {
    $this->client = $client;
    $this->loggerFactory = $logger_factory;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('logger.factory')
    );
  }

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
      $this->loggerFactory->get('feeds')->warning('%error', ['%error' => $e->getMessage()]);
      return;
    }

    // Response failed. Deleting the subscription will make it re-subscribe on
    // the next fetch.
    if ($response->getStatusCode() != 202) {
      $subscription->delete();
    }
  }

}
