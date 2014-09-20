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
 */
class DynamicItemTest extends UnitTestCase {
  public static function getInfo() {
    return array(
      'name' => 'Feed item: Dynamic',
      'description' => 'Tests dynamic feed item.',
      'group' => 'Feeds',
    );
  }

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
