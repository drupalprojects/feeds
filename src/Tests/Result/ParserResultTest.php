<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Result\ParserResultTest.
 */

namespace Drupal\feeds\Tests\Result;

use Drupal\feeds\Result\ParserResult;
use Drupal\feeds\Tests\FeedsUnitTestCase;

/**
 * @covers \Drupal\feeds\Result\ParserResult
 * @group Feeds
 */
class ParserResultTest extends FeedsUnitTestCase {

  public function test() {
    $result = new ParserResult();
    $this->assertSame($result, $result->set('attr', 'value1'));
    $this->assertSame($result->get('attr'), 'value1');

    // Test item adding.
    $item = $this->getMock('Drupal\feeds\Feeds\Item\ItemInterface');
    $this->assertSame($result, $result->addItem($item));

    // Check countable.
    $this->assertSame(1, count($result));
  }

}
