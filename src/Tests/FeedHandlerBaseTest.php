<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\FeedHandlerBaseTest.
 */

namespace Drupal\feeds\Tests;

use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * @covers \Drupal\feeds\FeedHandlerBase
 * @group Feeds
 */
class FeedHandlerBaseTest extends FeedsUnitTestCase {

  protected $lock;
  protected $handler;
  protected $feed;

  public function setUp() {
    parent::setUp();

    $this->lock = $this->getMock('Drupal\Core\Lock\LockBackendInterface');
    $container = new ContainerBuilder();
    $container->set('event_dispatcher', $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface'));
    $container->set('lock', $this->lock);

    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');

    $mock = $this->getMockForAbstractClass('Drupal\feeds\FeedHandlerBase', [], '', FALSE);
    $mock_class = get_class($mock);
    $this->handler = $mock_class::createInstance($container, $entity_type);

    $this->feed = $this->getMock('Drupal\feeds\FeedInterface');
    $this->feed->expects($this->any())
      ->method('id')
      ->will($this->returnValue(10));
  }

  public function testAcquireLock() {
    $this->lock->expects($this->once())
      ->method('acquire')
      ->with("feeds_feed_{$this->feed->id()}", 60.0)
      ->will($this->returnValue(TRUE));

    $method = $this->getMethod('Drupal\feeds\FeedHandlerBase', 'acquireLock');
    $method->invokeArgs($this->handler, [$this->feed]);
  }

  /**
   * @expectedException \Drupal\feeds\Exception\LockException
   */
  public function testException() {
    $method = $this->getMethod('Drupal\feeds\FeedHandlerBase', 'acquireLock');
    $method->invokeArgs($this->handler, [$this->feed]);
  }

  public function testReleaseLock() {
    $this->lock->expects($this->once())
      ->method('release')
      ->with("feeds_feed_{$this->feed->id()}")
      ->will($this->returnValue(TRUE));
    $method = $this->getMethod('Drupal\feeds\FeedHandlerBase', 'releaseLock');
    $method->invokeArgs($this->handler, [$this->feed]);
  }

}
