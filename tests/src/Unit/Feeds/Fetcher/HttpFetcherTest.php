<?php

/**
 * @file
 * Contains \Drupal\Tests\feeds\Unit\Feeds\Fetcher\HttpFetcherTest.
 */

namespace Drupal\Tests\feeds\Unit\Feeds\Fetcher;

use Drupal\Core\Form\FormState;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;
use Drupal\feeds\Feeds\Fetcher\HttpFetcher;
use Drupal\feeds\State;
use Drupal\feeds\StateInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Fetcher\HttpFetcher
 * @group feeds
 */
class HttpFetcherTest extends FeedsUnitTestCase {

  protected $cache;
  protected $client;
  protected $fetcher;
  protected $mockHandler;
  protected $state;

  public function setUp() {
    parent::setUp();

    $feed_type = $this->getMock('Drupal\feeds\FeedTypeInterface');
    $this->mockHandler = new MockHandler();
    $this->client = new Client(['handler' => HandlerStack::create($this->mockHandler)]);
    $this->cache = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');

    $this->fetcher = new HttpFetcher(['feed_type' => $feed_type], 'http', [], $this->client, $this->cache);
    $this->fetcher->setStringTranslation($this->getStringTranslationStub());

    $this->state = $this->getMock('Drupal\feeds\StateInterface');
  }

  public function testFetch() {
    mkdir('vfs://feeds/private');

    $feed = $this->getMock('Drupal\feeds\FeedInterface');
    file_put_contents('vfs://feeds/test_data', 'test data');
    $this->mockHandler->append(new Response(200, [], new Stream(fopen('vfs://feeds/test_data', 'r+'))));
    $result = $this->fetcher->fetch($feed, $this->state);
    $this->assertSame('test data', $result->getRaw());
  }

  /**
   * @expectedException \Drupal\feeds\Exception\EmptyFeedException
   */
  public function testFetch304() {
    $state = new State();
    $feed = $this->getMock('Drupal\feeds\FeedInterface');

    $this->mockHandler->append(new Response(304));

    $this->fetcher->fetch($feed, $this->state);
  }

  /**
   * @expectedException \RuntimeException
   */
  public function testFetch404() {
    $this->mockHandler->append(new Response(404));
    $this->fetcher->fetch($this->getMock('Drupal\feeds\FeedInterface'), $this->state);
  }

  /**
   * @expectedException \RuntimeException
   */
  public function testFetchError() {
    $this->mockHandler->append(new RequestException('', new Request(200, 'http://google.com')));
    $this->fetcher->fetch($this->getMock('Drupal\feeds\FeedInterface'), $this->state);
  }

  public function testFeedForm() {
    $feed = $this->getMock('Drupal\feeds\FeedInterface');

    $form_state = new FormState();
    $form = $this->fetcher->buildFeedForm([], $form_state, $feed);

    // @todo Validate now calls download, fix this test.
    // $this->fetcher->validateFeedForm($form, $form_state, $feed);

    // $this->assertSame(count($this->fetcher->sourceDefaults()), 1);
  }

  public function testOnFeedDeleteMultiple() {
    $feed = $this->getMock('Drupal\feeds\FeedInterface');
    $feed->expects($this->exactly(3))
      ->method('getSource')
      ->will($this->returnValue('http://example.com'));
    $feeds = [$feed, $feed, $feed];

    $this->fetcher->onFeedDeleteMultiple($feeds, $this->state);
  }

}

