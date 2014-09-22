<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\ParseEventTest.
 */

namespace Drupal\feeds\Tests\Event;

use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\Tests\FeedsUnitTestCase;

/**
 * @covers \Drupal\feeds\Event\ParseEvent
 */
class ParseEventTest extends FeedsUnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Event: Parse',
      'description' => 'Tests the fetch event.',
      'group' => 'Feeds',
    );
  }

  public function test() {
    $feed = $this->getMock('Drupal\feeds\FeedInterface');
    $fetcher_result = $this->getMock('Drupal\feeds\Result\FetcherResultInterface');
    $parser_result = $this->getMock('Drupal\feeds\Result\ParserResultInterface');
    $event = new ParseEvent($feed, $fetcher_result);

    $this->assertSame($fetcher_result, $event->getFetcherResult());

    $event->setParserResult($parser_result);
    $this->assertSame($parser_result, $event->getParserResult());
  }

}
