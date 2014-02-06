<?php

/**
 * @file
 * Contains \Drupal\node\Tests\Plugin\views\field\FeedsTest.
 */

namespace Drupal\feeds\Tests;

use Drupal\Tests\UnitTestCase;

/**
 * Tests the node bulk form plugin.
 */
class FeedsTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Feeds: first test',
      'description' => 'Tests the node bulk form plugin.',
      'group' => 'Feeds',
    );
  }

  public function test() {
    $this->assertTrue(TRUE);
    $this->assertTrue(TRUE);
  }

}
