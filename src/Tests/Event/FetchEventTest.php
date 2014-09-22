<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\FetchEventTest.
 */

namespace Drupal\feeds\Tests\Event;

use Drupal\feeds\Event\FetchEvent;
use Drupal\feeds\Tests\FeedsUnitTestCase;

/**
 * @coversDefaultClass \Drupal\feeds\Event\FetchEvent
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
