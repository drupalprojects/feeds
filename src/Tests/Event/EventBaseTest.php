<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\EventBaseTest.
 */

namespace Drupal\feeds\Tests\Event;

use Drupal\feeds\Tests\FeedsUnitTestCase;

/**
 * @covers \Drupal\feeds\Event\EventBase
 * @group Feeds
 */
class EventBaseTest extends FeedsUnitTestCase {

  public function test() {
    $feed = $this->getMock('Drupal\feeds\FeedInterface');
    $event = $this->getMockForAbstractClass('Drupal\feeds\Event\EventBase', [$feed]);
    $this->assertSame($feed, $event->getFeed());
  }

}
