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
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Fetcher\HttpFetcher
 * @group feeds
 */
class HttpFetcherTest extends FeedsUnitTestCase {

  protected $fetcher;
  protected $mockHandler;

  public function setUp() {
    parent::setUp();

    $feed_type = $this->getMock('Drupal\feeds\FeedTypeInterface');
    $this->mockHandler = new MockHandler();
    $client = new Client(['handler' => HandlerStack::create($this->mockHandler)]);
    $cache = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');

    $file_system = $this->prophesize('Drupal\Core\File\FileSystemInterface');
    $file_system->tempnam(Argument::type('string'), Argument::type('string'))->will(function ($args) {
      return tempnam($args[0], $args[1]);
    });
    $file_system->realpath(Argument::type('string'))->will(function ($args) {
      return realpath($args[0]);
    });

    $this->fetcher = new HttpFetcher(['feed_type' => $feed_type], 'http', [], $client, $cache, $file_system->reveal());
    $this->fetcher->setStringTranslation($this->getStringTranslationStub());
  }

  public function testFetch() {
    $this->mockHandler->append(function ($request, $options) {
      file_put_contents($options['sink'], 'test data');

      return new Response(200);
    });
    $result = $this->fetcher->fetch($this->getMock('Drupal\feeds\FeedInterface'), new State());
    $this->assertSame('test data', $result->getRaw());

    // Clean up test file.
    unlink($result->getFilePath());
  }

  /**
   * @expectedException \Drupal\feeds\Exception\EmptyFeedException
   */
  public function testFetch304() {
    $this->mockHandler->append(new Response(304));
    $this->fetcher->fetch($this->getMock('Drupal\feeds\FeedInterface'), new State());
  }

  /**
   * @expectedException \RuntimeException
   */
  public function testFetch404() {
    $this->mockHandler->append(new Response(404));
    $this->fetcher->fetch($this->getMock('Drupal\feeds\FeedInterface'), new State());
  }

  /**
   * @expectedException \RuntimeException
   */
  public function testFetchError() {
    $this->mockHandler->append(new RequestException('', new Request(200, 'http://google.com')));
    $this->fetcher->fetch($this->getMock('Drupal\feeds\FeedInterface'), new State());
  }

  public function testFeedForm() {
    $form_state = new FormState();
    $form = $this->fetcher->buildFeedForm([], $form_state, $this->getMock('Drupal\feeds\FeedInterface'));

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

    $this->fetcher->onFeedDeleteMultiple($feeds, new State());
  }

}

