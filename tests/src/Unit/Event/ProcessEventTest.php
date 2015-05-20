<?php

/**
 * @file
 * Contains \Drupal\Tests\feeds\Unit\ProcessEventTest.
 */

namespace Drupal\Tests\feeds\Unit\Event;

use Drupal\feeds\Event\ProcessEvent;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;

/**
 * Tests \Drupal\feeds\Event\ProcessEvent.
 *
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
