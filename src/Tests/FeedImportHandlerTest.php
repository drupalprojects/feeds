<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\FeedImportHandlerTest.
 */

namespace Drupal\feeds\Tests;

use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\FetchEvent;
use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\Event\ProcessEvent;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\FeedImportHandler;
use Drupal\feeds\StateInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @covers \Drupal\feeds\FeedImportHandler
 * @group Feeds
 */
class FeedImportHandlerTest extends FeedsUnitTestCase {

  protected $dispatcher;
  protected $feed;

  public function setUp() {
    parent::setUp();

    $this->dispatcher = new EventDispatcher();
    $this->handler = new FeedImportHandler($this->dispatcher);
    $this->handler->setStringTranslation($this->getStringTranslationStub());

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

  public function testStartBatchImport() {
    $this->feed
      ->expects($this->once())
      ->method('lock')
      ->will($this->returnValue($this->feed));

    $this->handler->startBatchImport($this->feed);
  }

  public function testBatchComplete() {
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

    $result = $this->handler->import($this->feed);
    $this->assertSame(StateInterface::BATCH_COMPLETE, $result);
  }

  public function testEmptyException() {
    $this->feed
      ->expects($this->once())
      ->method('save');

    $this->dispatcher->addListener(FeedsEvents::FETCH, function(FetchEvent $event) {
      throw new EmptyFeedException();
    });

    $this->handler->import($this->feed);
  }

  /**
   * @expectedException \Exception
   */
  public function testException() {
    $this->feed
      ->expects($this->once())
      ->method('save');
    $this->feed
      ->expects($this->once())
      ->method('cleanUp');

    $this->dispatcher->addListener(FeedsEvents::FETCH, function(FetchEvent $event) {
      throw new \Exception();
    });

    $this->handler->import($this->feed);
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

    $this->feed
      ->expects($this->once())
      ->method('lock')
      ->will($this->returnValue($this->feed));
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

    $this->handler->pushImport($this->feed, 'ABCD');
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
