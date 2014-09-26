<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\FeedHandlerBaseTest.
 */

namespace Drupal\feeds\Tests;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\feeds\StateInterface;

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
    $container->set('feeds.lock.persistent', $this->lock);

    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');

    $mock = $this->getMockForAbstractClass('Drupal\feeds\FeedHandlerBase', [], '', FALSE);
    $mock_class = get_class($mock);
    $this->handler = $mock_class::createInstance($container, $entity_type);

    $this->feed = $this->getMock('Drupal\feeds\FeedInterface');
    $this->feed->expects($this->any())
      ->method('id')
      ->will($this->returnValue(10));
  }

  public function testContineBatch() {
    $container = new ContainerBuilder();
    \Drupal::setContainer($container);

    $this->feed->expects($this->once())
      ->method('import')
      ->will($this->returnValue(StateInterface::BATCH_COMPLETE));

    $manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
    $storage->expects($this->once())
      ->method('load')
      ->with(10)
      ->will($this->returnValue($this->feed));
    $manager->expects($this->once())
      ->method('getStorage')
      ->with('feeds_feed')
      ->will($this->returnValue($storage));
    $container->set('entity.manager', $manager);

    $context = [];
    $this->handler->contineBatch($this->feed->id(), 'import', $context);
    $this->assertSame(StateInterface::BATCH_COMPLETE, $context['finished']);
  }

  public function testContineBatchException() {
    $container = new ContainerBuilder();
    \Drupal::setContainer($container);

    $manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $manager->expects($this->once())
      ->method('getStorage')
      ->with('feeds_feed')
      ->will($this->throwException(new \Exception));
    $container->set('entity.manager', $manager);

    $context = [];
    $this->handler->contineBatch($this->feed->id(), 'import', $context);
    $this->assertSame(StateInterface::BATCH_COMPLETE, $context['finished']);
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
