<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\FeedClearHandlerTest.
 */

namespace Drupal\feeds\Tests;

use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\FeedClearHandler;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @covers \Drupal\feeds\FeedClearHandler
 * @group Feeds
 */
class FeedClearHandlerTest extends FeedsUnitTestCase {

  protected $dispatcher;
  protected $feed;

  public function setUp() {
    parent::setUp();

    $this->dispatcher = new EventDispatcher();
    $this->handler = new FeedClearHandler($this->dispatcher);
    $this->handler->setStringTranslation($this->getStringTranslationStub());

    $this->feed = $this->getMock('Drupal\feeds\FeedInterface');
  }

  public function testStartBatchClear() {
    $this->feed
      ->expects($this->once())
      ->method('lock')
      ->will($this->returnValue($this->feed));

    $this->handler->startBatchClear($this->feed);
  }

  public function testClear() {
    $this->feed
      ->expects($this->exactly(2))
      ->method('progressClearing')
      ->will($this->onConsecutiveCalls(0.5, 1.0));
    $this->feed
      ->expects($this->once())
      ->method('clearState');

    $result = $this->handler->clear($this->feed);
    $this->assertSame($result, 0.5);
    $result = $this->handler->clear($this->feed);
    $this->assertSame($result, 1.0);
  }

  /**
   * @expectedException \Exception
   */
  public function testException() {
    $this->dispatcher->addListener(FeedsEvents::CLEAR, function($event) {
      throw new \Exception();
    });

    $this->feed
      ->expects($this->once())
      ->method('unlock');
    $this->feed
      ->expects($this->once())
      ->method('clearState');

    $this->handler->clear($this->feed);
  }

}
