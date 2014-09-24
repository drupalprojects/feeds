<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\FeedExpireHandlerTest.
 */

namespace Drupal\feeds\Tests;

use Drupal\Tests\UnitTestCase;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\FeedExpireHandler;
use Drupal\feeds\FeedInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @covers \Drupal\feeds\FeedExpireHandler
 * @group Feeds
 */
class FeedExpireHandlerTest extends UnitTestCase {

  protected $dispatcher;
  protected $lock;
  protected $feed;

  public function setUp() {
    parent::setUp();

    if (!defined('WATCHDOG_NOTICE')) {
      define('WATCHDOG_NOTICE', 5);
    }

    $this->dispatcher = new EventDispatcher();
    $this->lock = $this->getMock('Drupal\Core\Lock\LockBackendInterface');
    $this->lock
      ->expects($this->any())
      ->method('acquire')
      ->will($this->returnValue(TRUE));

    $this->feed = $this->getMock('Drupal\feeds\FeedInterface');
  }

  public function testExpire() {
    $this->feed
      ->expects($this->exactly(2))
      ->method('progressExpiring')
      ->will($this->onConsecutiveCalls(0.5, 1.0));
    $this->feed
      ->expects($this->once())
      ->method('clearState');

    $handler = new FeedExpireHandler($this->dispatcher, $this->lock);
    $result = $handler->expire($this->feed);
    $this->assertSame($result, 0.5);
    $result = $handler->expire($this->feed);
    $this->assertSame($result, 1.0);
  }

  /**
   * @expectedException \Exception
   */
  public function testException() {
    $this->dispatcher->addListener(FeedsEvents::EXPIRE, function($event) {
      throw new \Exception();
    });

    $this->feed
      ->expects($this->once())
      ->method('clearState');

    $handler = new FeedExpireHandler($this->dispatcher, $this->lock);
    $handler->expire($this->feed);
  }

}
