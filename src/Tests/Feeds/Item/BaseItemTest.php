<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Feeds\Item\BaseItemTest
 */

namespace Drupal\feeds\Tests\Feeds\Item;

use Drupal\feeds\Result\ParserResult;
use Drupal\feeds\Tests\FeedsUnitTestCase;

/**
 * @covers \Drupal\feeds\Feeds\Item\BaseItem
 * @group Feeds
 */
class BaseItemTest extends FeedsUnitTestCase {

  /**
   * Tests basic behavior.
   */
  public function test() {
    $item = $this->getMockForAbstractClass('Drupal\feeds\Feeds\Item\BaseItem');
    $item->set('field', 'value');
    $this->assertSame($item->get('field'), 'value');
    $item->setResult(new ParserResult());
  }

}
