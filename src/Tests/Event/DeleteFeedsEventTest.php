<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\DeleteFeedsEventTest.
 */

namespace Drupal\feeds\Tests\Event;

use Drupal\feeds\Event\DeleteFeedsEvent;
use Drupal\feeds\Tests\FeedsUnitTestCase;

/**
 * @covers \Drupal\feeds\Event\DeleteFeedsEvent
 */
class DeleteFeedsEventTest extends FeedsUnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Event: Delete',
      'description' => 'Tests the delete event.',
      'group' => 'Feeds',
    );
  }

  public function test() {
    $feeds = array($this->getMock('Drupal\feeds\FeedInterface'));
    $event = new DeleteFeedsEvent($feeds);

    $this->assertSame($feeds, $event->getFeeds());
  }

}
