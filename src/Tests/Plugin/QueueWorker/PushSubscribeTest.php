<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Plugin\QueueWorker\PushSubscribeTest.
 */

namespace Drupal\feeds\Tests\Plugin\QueueWorker;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\feeds\Plugin\QueueWorker\PushSubscribe;
use Drupal\feeds\Tests\FeedsUnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock;

/**
 * @covers \Drupal\feeds\Plugin\QueueWorker\PushSubscribe
 * @group Feeds
 */
class PushSubscribeTest extends FeedsUnitTestCase {

  protected $client;
  protected $plugin;
  protected $mock;

  protected $hub;

  public function setUp() {
    parent::setUp();
    $container = new ContainerBuilder();
    $this->client = new Client();
    $this->mock = new Mock();
    $this->client->getEmitter()->attach($this->mock);

    $container->set('http_client', $this->client);

    $logger_factory = $this->getMock('Drupal\Core\Logger\LoggerChannelFactoryInterface');
    $logger_factory->expects($this->any())
      ->method('get')
      ->will($this->returnValue($this->getMock('Drupal\Core\Logger\LoggerChannelInterface')));
    $container->set('logger.factory', $logger_factory);
    $this->plugin = PushSubscribe::create($container, [], 'feeds_push_subscribe', []);
    $this->plugin->setUrlGenerator($this->getMock('Drupal\Core\Routing\UrlGeneratorInterface'));
  }

  public function testGoodSubscribe() {
    // Check invalid argument passes.
    $this->plugin->processItem(NULL);

    $stream = Stream::factory(fopen('php://memory', 'r+'));
    $this->mock->addResponse(new Response(200, [], $stream));
    $this->mock->addResponse(new Response(400, [], $stream));

    $subscription = $this->getMock('Drupal\feeds\SubscriptionInterface');

    $subscription->expects($this->any())
      ->method('getHub')
      ->will($this->returnValue('http://example.com'));
    $subscription->expects($this->any())
      ->method('getState')
      ->will($this->returnValue('subscribing'));

    $this->plugin->processItem($subscription);

    // Test 404.
    $this->plugin->processItem($subscription);
  }

}
