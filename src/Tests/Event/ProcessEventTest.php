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
 */
class ProcessEventTest extends FeedsUnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Event: Process',
      'description' => 'Tests the process event.',
      'group' => 'Feeds',
    );
  }

  public function test() {
    $feed = $this->getMock('Drupal\feeds\FeedInterface');
    $result = $this->getMock('Drupal\feeds\Result\ParserResultInterface');
    $event = new ProcessEvent($feed, $result);

    $this->assertSame($result, $event->getParserResult());
  }

}
