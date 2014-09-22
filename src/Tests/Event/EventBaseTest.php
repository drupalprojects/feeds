<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\EventBaseTest.
 */

namespace Drupal\feeds\Tests\Event;

use Drupal\feeds\Event\EventBase;
use Drupal\feeds\Tests\FeedsUnitTestCase;

/**
 * @covers \Drupal\feeds\Event\EventBase
 */
class EventBaseTest extends FeedsUnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Event: Base class',
      'description' => 'Tests the event base class.',
      'group' => 'Feeds',
    );
  }

  public function test() {
    $feed = $this->getMock('Drupal\feeds\FeedInterface');
    $event = $this->getMockForAbstractClass('Drupal\feeds\Event\EventBase', array($feed));
    $this->assertSame($feed, $event->getFeed());
  }

}
