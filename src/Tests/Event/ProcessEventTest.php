<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\ProcessEventTest.
 */

namespace Drupal\feeds\Tests\Event;

use Drupal\feeds\Event\ProcessEvent;
use Drupal\feeds\Tests\FeedsUnitTestCase;

/**
 * @covers \Drupal\feeds\Event\ProcessEvent
 * @group Feeds
 */
class ProcessEventTest extends FeedsUnitTestCase {

  public function test() {
    $feed = $this->getMock('Drupal\feeds\FeedInterface');
    $item = $this->getMock('Drupal\feeds\Feeds\Item\ItemInterface');
    $event = new ProcessEvent($feed, $item);

    $this->assertSame($item, $event->getParserResult());
  }

}
