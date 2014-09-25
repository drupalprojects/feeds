<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Feeds\Target\PathTest.
 */

namespace Drupal\feeds\Tests\Feeds\Target;

use Drupal\feeds\Feeds\Target\Path;
use Drupal\feeds\Tests\FeedsUnitTestCase;

/**
 * @covers \Drupal\feeds\Feeds\Target\Path
 */
class PathTest extends FeedsUnitTestCase {

  public function testPrepareValue() {
    $importer = $this->getMock('Drupal\feeds\ImporterInterface');
    $target = new Path(['importer' => $importer], 'path', []);

    $method = $this->getProtectedClosure($target, 'prepareValue');

    $values = ['alias' => 'path '];
    $method(0, $values);
    $this->assertSame($values['alias'], 'path');
  }

  public function testPrepareTarget() {
    $method = $this->getMethod('Drupal\feeds\Feeds\Target\Path', 'prepareTarget')->getClosure();
    $targets = ['properties' => ['pid' => '']];
    $method($targets);
    $this->assertSame($targets, ['properties' => []]);
  }

}
