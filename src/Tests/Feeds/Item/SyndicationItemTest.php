<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Feeds\Item\SyndicationItemTest
 */

namespace Drupal\feeds\Tests\Feeds\Item;

use Drupal\Tests\UnitTestCase;
use Drupal\feeds\Feeds\Item\SyndicationItem;
use Drupal\feeds\Result\ParserResult;

/**
 * @covers \Drupal\feeds\Feeds\Item\SyndicationItem
 */
class SyndicationItemTest extends UnitTestCase {
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
    $item = new SyndicationItem();
    $item->set('field', 'value');
    $this->assertSame($item->get('field'), 'value');
    $item->setResult(new ParserResult());
  }

}
