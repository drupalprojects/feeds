<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Plugin\QueueWorker\FeedQueueWorkerBaseTest.
 */

namespace Drupal\feeds\Tests\Plugin\QueueWorker;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Result\FetcherResult;
use Drupal\feeds\Tests\FeedsUnitTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @covers \Drupal\feeds\Plugin\QueueWorker\FeedQueueWorkerBase
 * @group Feeds
 */
class FeedQueueWorkerBaseTest extends FeedsUnitTestCase {

  /**
   * @expectedException \RuntimeException
   */
  public function test() {
    $container = new ContainerBuilder();
    $container->set('queue', $this->getMock('Drupal\Core\Queue\QueueFactory', [], [], '', FALSE));
    $container->set('event_dispatcher', new EventDispatcher());

    $plugin = $this->getMockForAbstractClass('Drupal\feeds\Plugin\QueueWorker\FeedQueueWorkerBase', [], '', FALSE);
    $plugin = $plugin::create($container, [], '', []);

    $method = $this->getProtectedClosure($plugin, 'handleException');
    $method($this->getMockFeed(), new EmptyFeedException());
    $method($this->getMockFeed(), new \RuntimeException());
  }

}
