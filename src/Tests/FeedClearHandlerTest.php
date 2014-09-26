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
  protected $lock;
  protected $feed;

  public function setUp() {
    parent::setUp();

    $this->dispatcher = new EventDispatcher();
    $this->lock = $this->getMock('Drupal\Core\Lock\LockBackendInterface');
    $this->handler = new FeedClearHandler($this->dispatcher, $this->lock);
    $this->handler->setStringTranslation($this->getStringTranslationStub());

    $this->lock
      ->expects($this->any())
      ->method('acquire')
      ->will($this->returnValue(TRUE));

    $this->feed = $this->getMock('Drupal\feeds\FeedInterface');
  }

  public function testStartBatchClear() {
    $this->lock
      ->expects($this->once())
      ->method('acquire')
      ->will($this->returnValue(TRUE));

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
      ->method('clearState');

    $this->handler->clear($this->feed);
  }

}
