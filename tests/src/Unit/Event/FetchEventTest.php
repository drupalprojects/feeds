<?php

/**
 * @file
 * Contains \Drupal\Tests\feeds\Unit\FetchEventTest.
 */

namespace Drupal\Tests\feeds\Unit\Event;

use Drupal\feeds\Event\FetchEvent;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;

/**
 * Tests \Drupal\feeds\Event\FetchEvent.
 *
 * @covers \Drupal\feeds\Event\FetchEvent
 * @group Feeds
 */
class FetchEventTest extends FeedsUnitTestCase {

  public function test() {
    $feed = $this->getMock('Drupal\feeds\FeedInterface');
    $result = $this->getMock('Drupal\feeds\Result\FetcherResultInterface');
    $event = new FetchEvent($feed);

    $event->setFetcherResult($result);
    $this->assertSame($result, $event->getFetcherResult());
  }

}
