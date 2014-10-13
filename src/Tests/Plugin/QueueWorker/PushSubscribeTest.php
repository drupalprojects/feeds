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

  public function testHeaderLink() {
    // Check invalid argument passes.
    $this->plugin->processItem(NULL);

    $stream = Stream::factory(fopen('php://memory', 'r+'));
    $headers =  [
      'Link' => [
        '<http://blog.superfeedr.com/my-resource>; rel="self"',
        '<http://pubsubhubbub.superfeedr.com>; rel="hub"',
      ],
    ];

    $this->mock->addResponse(new Response(200, $headers, $stream));
    $this->mock->addResponse(new Response(200, [], $stream));

    $subscription = $this->getMock('Drupal\feeds\SubscriptionInterface');
    $subscription->expects($this->any())
      ->method('getHub')
      ->will($this->returnCallback(function () {
        return $this->hub;
      }));
    $subscription->expects($this->any())
      ->method('setHub')
      ->will($this->returnCallback(function ($hub) {
        $this->hub = $hub;
      }));

    $this->plugin->processItem($subscription);
  }

}
