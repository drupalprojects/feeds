<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\EventDispatcherTraitTest.
 */

namespace Drupal\feeds\Tests\Event;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\feeds\Event\EventDispatcherTrait;
use Drupal\feeds\Tests\FeedsUnitTestCase;
use Symfony\Component\EventDispatcher\Event;

/**
 * @coversDefaultClass \Drupal\feeds\Event\EventDispatcherTrait
 * @group Feeds
 */
class EventDispatcherTraitTest extends FeedsUnitTestCase {

  public function test() {
    $mock = $this->getMockForTrait('Drupal\feeds\Event\EventDispatcherTrait');
    $dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

    $container = new ContainerBuilder();
    $container->set('event_dispatcher', $dispatcher);
    \Drupal::setContainer($container);
    $method = $this->getMethod(get_class($mock), 'getEventDispatcher');
    $this->assertSame($dispatcher, $method->invokeArgs($mock, array()));

    $this->assertSame($mock, $mock->setEventDispatcher($dispatcher));
    $this->assertSame($dispatcher, $method->invokeArgs($mock, array()));

    $event = new Event();
    $dispatcher->expects($this->once())
      ->method('dispatch')
      ->with('test_event', $event);
    $method = $this->getMethod(get_class($mock), 'dispatchEvent');
    $method->invokeArgs($mock, array('test_event', $event));
  }

}
