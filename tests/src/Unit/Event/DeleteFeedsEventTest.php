<?php

/**
 * @file
 * Contains \Drupal\Tests\feeds\Unit\DeleteFeedsEventTest.
 */

namespace Drupal\Tests\feeds\Unit\Event;

use Drupal\feeds\Event\DeleteFeedsEvent;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;

/**
 * @covers \Drupal\feeds\Event\DeleteFeedsEvent
 * @group Feeds
 */
class DeleteFeedsEventTest extends FeedsUnitTestCase {

  public function test() {
    $feeds = [$this->getMock('Drupal\feeds\FeedInterface')];
    $event = new DeleteFeedsEvent($feeds);

    $this->assertSame($feeds, $event->getFeeds());
  }

}
