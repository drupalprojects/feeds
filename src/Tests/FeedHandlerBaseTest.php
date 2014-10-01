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

  public function test() {
    $container = new ContainerBuilder();
    $container->set('event_dispatcher', $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface'));

    $mock = $this->getMockForAbstractClass('Drupal\feeds\FeedHandlerBase', [], '', FALSE);
    $mock_class = get_class($mock);
    $hander = $mock_class::createInstance($container, $this->getMock('Drupal\Core\Entity\EntityTypeInterface'));
  }

}
