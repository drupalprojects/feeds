<?php

/**
 * @file
 * Contains \Drupal\Tests\feeds\Unit\Feeds\Item\BaseItemTest
 */

namespace Drupal\Tests\feeds\Unit\Feeds\Item;

use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;

/**
 * Tests \Drupal\feeds\Feeds\Item\BaseItem.
 *
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
  }

}
