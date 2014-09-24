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
 * @group Feeds
 */
class DeleteFeedsEventTest extends FeedsUnitTestCase {

  public function test() {
    $feeds = array($this->getMock('Drupal\feeds\FeedInterface'));
    $event = new DeleteFeedsEvent($feeds);

    $this->assertSame($feeds, $event->getFeeds());
  }

}
