<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Feeds\Item\DynamicItemTest
 */

namespace Drupal\feeds\Tests\Feeds\Item;

use Drupal\Tests\UnitTestCase;
use Drupal\feeds\Feeds\Item\DynamicItem;
use Drupal\feeds\Result\ParserResult;

/**
 * @covers \Drupal\feeds\Feeds\Item\DynamicItem
 * @group Feeds
 */
class DynamicItemTest extends UnitTestCase {

  /**
   * Tests basic behavior.
   */
  public function test() {
    $item = new DynamicItem();
    $item->set('field', 'value');
    $this->assertSame($item->get('field'), 'value');
    $item->setResult(new ParserResult());
  }

}
