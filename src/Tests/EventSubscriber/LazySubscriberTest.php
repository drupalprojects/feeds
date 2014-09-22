<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\LazySubscriberTest.
 */

namespace Drupal\feeds\Tests\EventSubscriber;

use Drupal\feeds\EventSubscriber\LazySubscriber;
use Drupal\feeds\Event\ClearEvent;
use Drupal\feeds\Event\ExpireEvent;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\FetchEvent;
use Drupal\feeds\Event\InitEvent;
use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\Event\ProcessEvent;
use Drupal\feeds\Tests\FeedsUnitTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @covers \Drupal\feeds\EventSubscriber\LazySubscriber
 */
class LazySubscriberTest extends FeedsUnitTestCase {

  protected $dispatcher;
  protected $explodingDispatcher;
  protected $feed;
  protected $importer;
  protected $fetcher;
  protected $parser;
  protected $processor;


  public static function getInfo() {
    return array(
      'name' => 'Event subscriber: Lazy',
      'description' => 'Tests for the lazy event subscriber.',
      'group' => 'Feeds',
    );
  }

  public function setUp() {
    parent::setUp();

    $this->dispatcher = new EventDispatcher();

    // Dispatcher used to verify things only get called once.
    $this->explodingDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
    $this->explodingDispatcher->expects($this->any())
      ->method('addListener')
      ->will($this->throwException(new \Exception));

    $this->feed = $this->getMock('Drupal\feeds\FeedInterface');
    $this->importer = $this->getMock('Drupal\feeds\ImporterInterface');

    $this->fetcher = $this->getMock('Drupal\feeds\Plugin\Type\Fetcher\FetcherInterface');
    $this->parser = $this->getMock('Drupal\feeds\Plugin\Type\Parser\ParserInterface');
    $this->processor = $this->getMock('Drupal\feeds\Plugin\Type\Processor\ProcessorInterface');

    $this->feed
      ->expects($this->any())
      ->method('getImporter')
      ->will($this->returnValue($this->importer));
  }

  public function testGetSubscribedEvents() {
    $events = LazySubscriber::getSubscribedEvents();
    $this->assertSame(3, count($events));
  }

  public function testOnInitImport() {
    $fetcherResult = $this->getMock('Drupal\feeds\Result\FetcherResultInterface');
    $parserResult = $this->getMock('Drupal\feeds\Result\ParserResultInterface');

    $this->fetcher
      ->expects($this->once())
      ->method('fetch')
      ->with($this->feed)
      ->will($this->returnValue($fetcherResult));
    $this->parser
      ->expects($this->once())
      ->method('parse')
      ->with($this->feed, $fetcherResult)
      ->will($this->returnValue($parserResult));
    $this->processor
      ->expects($this->once())
      ->method('process')
      ->with($this->feed, $parserResult);

    $this->importer->expects($this->once())
      ->method('getFetcher')
      ->will($this->returnValue($this->fetcher));
    $this->importer->expects($this->once())
      ->method('getParser')
      ->will($this->returnValue($this->parser));
    $this->importer->expects($this->once())
      ->method('getProcessor')
      ->will($this->returnValue($this->processor));

    $subscriber = new LazySubscriber();
    $subscriber->onInitImport(new InitEvent($this->feed), FeedsEvents::INIT_IMPORT, $this->dispatcher);

    // Fetch.
    $fetch_event = new FetchEvent($this->feed);
    $this->dispatcher->dispatch(FeedsEvents::FETCH, $fetch_event);
    $this->assertSame($fetcherResult, $fetch_event->getFetcherResult());

    // Parse.
    $parse_event = new ParseEvent($this->feed, $fetcherResult);
    $this->dispatcher->dispatch(FeedsEvents::PARSE, $parse_event);
    $this->assertSame($parserResult, $parse_event->getParserResult());

    // Process.
    $this->dispatcher->dispatch(FeedsEvents::PROCESS, new ProcessEvent($this->feed, $parserResult));

    // Call again.
    $subscriber->onInitImport(new InitEvent($this->feed), FeedsEvents::INIT_IMPORT, $this->explodingDispatcher);
  }

  public function testOnInitClear() {
    $clearable = $this->getMock('Drupal\feeds\Plugin\Type\ClearableInterface');
    $clearable->expects($this->exactly(2))
      ->method('clear')
      ->with($this->feed);

    $this->importer->expects($this->once())
      ->method('getPlugins')
      ->will($this->returnValue(array($clearable, $this->dispatcher, $clearable)));

    $subscriber = new LazySubscriber();

    $subscriber->onInitClear(new InitEvent($this->feed), FeedsEvents::INIT_CLEAR, $this->dispatcher);
    $this->dispatcher->dispatch(FeedsEvents::CLEAR, new ClearEvent($this->feed));

    // Call again.
    $subscriber->onInitClear(new InitEvent($this->feed), FeedsEvents::INIT_CLEAR, $this->explodingDispatcher);
  }

  public function testOnInitExpire() {
    $this->importer->expects($this->once())
      ->method('getProcessor')
      ->will($this->returnValue($this->processor));
    $this->processor->expects($this->once())
      ->method('expire')
      ->with($this->feed);

    $subscriber = new LazySubscriber();
    $subscriber->onInitExpire(new InitEvent($this->feed), FeedsEvents::INIT_IMPORT, $this->dispatcher);
    $this->dispatcher->dispatch(FeedsEvents::EXPIRE, new ExpireEvent($this->feed));

    // Call again.
    $subscriber->onInitExpire(new InitEvent($this->feed), FeedsEvents::INIT_IMPORT, $this->explodingDispatcher);
  }

}
