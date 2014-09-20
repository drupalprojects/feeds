<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\FeedImportHandlerTest.
 */

namespace Drupal\feeds\Tests;

use Drupal\Tests\UnitTestCase;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\FetchEvent;
use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\Event\ProcessEvent;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\FeedImportHandler;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\StateInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @covers \Drupal\feeds\FeedImportHandler
 */
class FeedImportHandlerTest extends UnitTestCase {

  protected $dispatcher;
  protected $lock;
  protected $feed;

  public static function getInfo() {
    return array(
      'name' => 'Feed handler: Import',
      'description' => 'Tests the feed import handler.',
      'group' => 'Feeds',
    );
  }

  public function setUp() {
    parent::setUp();

    if (!defined('WATCHDOG_NOTICE')) {
      define('WATCHDOG_NOTICE', 5);
    }
    if (!defined('WATCHDOG_INFO')) {
      define('WATCHDOG_INFO', 6);
    }

    $this->dispatcher = new EventDispatcher();
    $this->lock = $this->getMock('Drupal\Core\Lock\LockBackendInterface');
    $this->feed = $this->getMock('Drupal\feeds\FeedInterface');
    $this->feed
      ->expects($this->any())
      ->method('id')
      ->will($this->returnValue(10));
    $this->feed
      ->expects($this->any())
      ->method('bundle')
      ->will($this->returnValue('test_feed'));
  }

  public function testImport() {
    $this->lock
      ->expects($this->once())
      ->method('acquire')
      ->will($this->returnValue(TRUE));

    $this->lock
      ->expects($this->never())
      ->method('release');

    $this->feed
      ->expects($this->once())
      ->method('save');

    $this->addDefaultEventListeners();

    $handler = new FeedImportHandler($this->dispatcher, $this->lock);
    $handler->import($this->feed);
  }

  public function testBatchComplete() {
    $this->lock
      ->expects($this->once())
      ->method('acquire')
      ->will($this->returnValue(TRUE));

    $this->addDefaultEventListeners();

    $this->feed
      ->expects($this->once())
      ->method('save');
    $this->feed
      ->expects($this->once())
      ->method('cleanUp');
    $this->feed
      ->expects($this->once())
      ->method('progressImporting')
      ->will($this->returnValue(StateInterface::BATCH_COMPLETE));

    $handler = new FeedImportHandler($this->dispatcher, $this->lock);
    $result = $handler->import($this->feed);
    $this->assertSame(StateInterface::BATCH_COMPLETE, $result);
  }

  public function testEmptyException() {
    $this->lock
      ->expects($this->once())
      ->method('acquire')
      ->will($this->returnValue(TRUE));

    $this->feed
      ->expects($this->once())
      ->method('save');

    $this->dispatcher->addListener(FeedsEvents::FETCH, function(FetchEvent $event) {
      throw new EmptyFeedException();
    });

    $handler = new FeedImportHandler($this->dispatcher, $this->lock);
    $handler->import($this->feed);
  }

  /**
   * @expectedException \Exception
   */
  public function testException() {
    $this->lock
      ->expects($this->once())
      ->method('acquire')
      ->will($this->returnValue(TRUE));

    $this->feed
      ->expects($this->once())
      ->method('save');
    $this->feed
      ->expects($this->once())
      ->method('cleanUp');

    $this->dispatcher->addListener(FeedsEvents::FETCH, function(FetchEvent $event) {
      throw new \Exception();
    });

    $handler = new FeedImportHandler($this->dispatcher, $this->lock);
    $handler->import($this->feed);
  }

  /**
   * @expectedException \Drupal\feeds\Exception\LockException
   * @expectedExceptionMessage Cannot acquire lock for feed test_feed / 10.
   */
  public function testLockException() {
    $this->feed
      ->expects($this->never())
      ->method('save');
    $handler = new FeedImportHandler($this->dispatcher, $this->lock);
    $handler->import($this->feed);
  }

  public function testPushImport() {
    // Fetch should not get called.
    $fetcher_result = $this->getMock('Drupal\feeds\Result\FetcherResultInterface');
    $parser_result = $this->getMock('Drupal\feeds\Result\ParserResultInterface');
    $this->dispatcher->addListener(FeedsEvents::FETCH, function(FetchEvent $event) {
      throw new \Exception();
    });
    $this->dispatcher->addListener(FeedsEvents::PARSE, function(ParseEvent $event) use ($fetcher_result, $parser_result) {
      $this->assertSame($event->getFetcherResult(), $fetcher_result);
      $event->setParserResult($parser_result);
    });

    $this->dispatcher->addListener(FeedsEvents::PROCESS, function(ProcessEvent $event) use ($parser_result) {
      $this->assertSame($event->getParserResult(), $parser_result);
    });

    $this->lock
      ->expects($this->any())
      ->method('acquire')
      ->will($this->returnValue(TRUE));

    $this->feed
      ->expects($this->once())
      ->method('setFetcherResult');
    $this->feed
      ->expects($this->any())
      ->method('getFetcherResult')
      ->will($this->returnValue($fetcher_result));
    $this->feed
      ->expects($this->any())
      ->method('progressParsing')
      ->will($this->returnValue(0));
    $this->feed
      ->expects($this->exactly(2))
      ->method('progressImporting')
      ->will($this->onConsecutiveCalls(0.5, 1.0));

    $handler = new FeedImportHandler($this->dispatcher, $this->lock);
    $handler->pushImport($this->feed, 'ABCD');
  }

  protected function addDefaultEventListeners() {
    $fetcher_result = $this->getMock('Drupal\feeds\Result\FetcherResultInterface');
    $parser_result = $this->getMock('Drupal\feeds\Result\ParserResultInterface');

    $this->dispatcher->addListener(FeedsEvents::FETCH, function(FetchEvent $event) use ($fetcher_result) {
      $event->setFetcherResult($fetcher_result);
    });

    $this->dispatcher->addListener(FeedsEvents::PARSE, function(ParseEvent $event) use ($fetcher_result, $parser_result) {
      $this->assertSame($event->getFetcherResult(), $fetcher_result);
      $event->setParserResult($parser_result);
    });

    $this->dispatcher->addListener(FeedsEvents::PROCESS, function(ProcessEvent $event) use ($parser_result) {
      $this->assertSame($event->getParserResult(), $parser_result);
    });
  }

}
