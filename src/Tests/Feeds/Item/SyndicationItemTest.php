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
 * @coversDefaultClass \Drupal\feeds\Feeds\Item\SyndicationItem
 * @group Feeds
 */
class SyndicationItemTest extends UnitTestCase {

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
