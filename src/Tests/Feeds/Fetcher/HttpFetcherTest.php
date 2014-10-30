<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Feeds\Fetcher\HttpFetcherTest.
 */

namespace Drupal\feeds\Tests\Feeds\Fetcher;

use Drupal\Core\Form\FormState;
use Drupal\feeds\Feeds\Fetcher\HttpFetcher;
use Drupal\feeds\State;
use Drupal\feeds\StateInterface;
use Drupal\feeds\Tests\FeedsUnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock;

/**
 * @covers \Drupal\feeds\Feeds\Fetcher\HttpFetcher
 * @group Feeds
 */
class HttpFetcherTest extends FeedsUnitTestCase {

  protected $fetcher;
  protected $client;
  protected $config;
  protected $cache;
  protected $state;

  public function setUp() {
    parent::setUp();

    $importer = $this->getMock('Drupal\feeds\ImporterInterface');
    $this->client = new Client();
    $this->config = $this->getMock('Drupal\Core\Config\ConfigFactoryInterface');
    $config = $this->getMock('Drupal\Core\Config\Config', [], [], '', FALSE);
    $config->expects($this->any())
      ->method('get')
      ->with('path.private')
      ->will($this->returnValue('vfs://feeds/private'));
    $this->config->expects($this->any())
      ->method('get')
      ->with('system.file')
      ->will($this->returnValue($config));
    $this->cache = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');

    $this->fetcher = new HttpFetcher(['importer' => $importer], 'http', [], $this->client, $this->config, $this->cache);
    $this->fetcher->setStringTranslation($this->getStringTranslationStub());

    $this->state = $this->getMock('Drupal\feeds\StateInterface');
  }

  public function testFetch() {
    mkdir('vfs://feeds/private');

    $feed = $this->getMock('Drupal\feeds\FeedInterface');
    file_put_contents('vfs://feeds/test_data', 'test data');
    $stream = Stream::factory(fopen('vfs://feeds/test_data', 'r+'));
    $this->client->getEmitter()->attach(new Mock([new Response(200, [], $stream)]));
    $result = $this->fetcher->fetch($feed, $this->state);
    $this->assertSame('test data', $result->getRaw());
  }

  /**
   * @expectedException \Drupal\feeds\Exception\EmptyFeedException
   */
  public function testFetch304() {
    $state = new State();
    $feed = $this->getMock('Drupal\feeds\FeedInterface');

    $this->client->getEmitter()->attach(new Mock([new Response(304)]));

    $this->fetcher->fetch($feed, $this->state);
  }

  /**
   * @expectedException \RuntimeException
   */
  public function testFetch404() {
    $this->client->getEmitter()->attach(new Mock([new Response(404)]));
    $this->fetcher->fetch($this->getMock('Drupal\feeds\FeedInterface'), $this->state);
  }

  /**
   * @expectedException \RuntimeException
   */
  public function testFetchError() {
    $this->client->getEmitter()->attach(new Mock([new RequestException('', new Request(200, 'http://google.com'))]));
    $this->fetcher->fetch($this->getMock('Drupal\feeds\FeedInterface'), $this->state);
  }

  public function testFeedForm() {
    $feed = $this->getMock('Drupal\feeds\FeedInterface');

    $form_state = new FormState();
    $form = $this->fetcher->buildFeedForm([], $form_state, $feed);
    $this->fetcher->validateFeedForm($form, $form_state, $feed);

    $this->assertSame(count($this->fetcher->sourceDefaults()), 1);
  }

  public function testOnFeedDeleteMultiple() {
    $feed = $this->getMock('Drupal\feeds\FeedInterface');
    $feed->expects($this->exactly(6))
      ->method('getSource')
      ->will($this->returnValue('http://example.com'));
    $feeds = [$feed, $feed, $feed];

    $this->fetcher->onFeedDeleteMultiple($feeds, $this->state);
  }

}

