<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Feeds\Fetcher\HttpFetcherTest.
 */

namespace Drupal\feeds\Tests\Feeds\Fetcher {

use Drupal\Core\Config\Config;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\feeds\Feeds\Fetcher\HttpFetcher;
use Drupal\feeds\Tests\FeedsUnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock;
use org\bovigo\vfs\vfsStream;

/**
 * @covers \Drupal\feeds\Feeds\Fetcher\HttpFetcher
 * @group Feeds
 */
class HttpFetcherTest extends FeedsUnitTestCase {

  protected $fetcher;
  protected $client;
  protected $config;
  protected $cache;

  public function setUp() {
    parent::setUp();

    if (!defined('FILE_MODIFY_PERMISSIONS')) {
      define('FILE_MODIFY_PERMISSIONS', 2);
    }
    if (!defined('FILE_CREATE_DIRECTORY')) {
      define('FILE_CREATE_DIRECTORY', 1);
    }
    if (!defined('FILE_EXISTS_REPLACE')) {
      define('FILE_EXISTS_REPLACE', 1);
    }

    $importer = $this->getMock('Drupal\feeds\ImporterInterface');
    $container = new ContainerBuilder();
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

    $container->set('http_client', $this->client);
    $container->set('config.factory', $this->config);
    $container->set('cache.default', $this->cache);

    $this->fetcher = HttpFetcher::create($container, ['importer' => $importer], 'http', []);
    $this->fetcher->setStringTranslation($this->getStringTranslationStub());
  }

  public function testFetch() {
    vfsStream::setup('feeds');
    mkdir('vfs://feeds/private');

    $feed = $this->getMock('Drupal\feeds\FeedInterface');
    file_put_contents('vfs://feeds/test_data', 'test data');
    $stream = Stream::factory(fopen('vfs://feeds/test_data', 'r+'));
    $this->client->getEmitter()->attach(new Mock([new Response(200, [], $stream)]));
    $result = $this->fetcher->fetch($feed);
    $this->assertSame('test data', $result->getRaw());
  }

  /**
   * @expectedException \Drupal\feeds\Exception\EmptyFeedException
   */
  public function testFetch304() {
    vfsStream::setup('feeds');

    $this->client->getEmitter()->attach(new Mock([new Response(304)]));
    $this->fetcher->fetch($this->getMock('Drupal\feeds\FeedInterface'));
  }

  /**
   * @expectedException \RuntimeException
   */
  public function testFetch404() {
    vfsStream::setup('feeds');

    $this->client->getEmitter()->attach(new Mock([new Response(404)]));
    $this->fetcher->fetch($this->getMock('Drupal\feeds\FeedInterface'));
  }

  /**
   * @expectedException \RuntimeException
   */
  public function testFetchError() {
    vfsStream::setup('feeds');

    $this->client->getEmitter()->attach(new Mock([new RequestException('', new Request(200, 'http://google.com'))]));
    $this->fetcher->fetch($this->getMock('Drupal\feeds\FeedInterface'));
  }

  public function testConfigurationForm() {
    $form_state = new FormState();
    $form = $this->fetcher->buildConfigurationForm([], $form_state);
    $this->assertSame(count($form), 4);
  }

  public function testFeedForm() {
    $feed = $this->getMock('Drupal\feeds\FeedInterface');

    $form_state = new FormState();
    $form = $this->fetcher->buildFeedForm([], $form_state, $feed);
    $this->fetcher->validateFeedForm($form, $form_state, $feed);

    $this->assertSame(count($this->fetcher->sourceDefaults()), 2);
  }

  public function testOnFeedDeleteMultiple() {
    $feed = $this->getMock('Drupal\feeds\FeedInterface');
    $feed->expects($this->exactly(6))
      ->method('getSource')
      ->will($this->returnValue('http://example.com'));
    $feeds = [$feed, $feed, $feed];

    $this->fetcher->onFeedDeleteMultiple($feeds);
  }

}
}

// @todo Remove.
namespace {
  if (!function_exists('drupal_tempnam')) {
    function drupal_tempnam($scheme, $dir) {
      mkdir('vfs://feeds/' . $dir);
      $file = 'vfs://feeds/' . $dir . '/' . mt_rand(10, 1000);
      touch($file);
      return $file;
    }
  }

  if (!function_exists('file_prepare_directory')) {
    function file_prepare_directory(&$directory) {
      return mkdir($directory);
    }
  }

  if (!function_exists('file_unmanaged_move')) {
    function file_unmanaged_move($old, $new) {
      rename($old, $new);
    }
  }

  if (!function_exists('drupal_set_message')) {
    function drupal_set_message() {}
  }

  if (!function_exists('watchdog')) {
    function watchdog() {}
  }

  if (!function_exists('file_unmanaged_delete')) {
    function file_unmanaged_delete() {}
  }
}
