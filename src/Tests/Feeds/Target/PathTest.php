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
    $method = $this->getMethod('Drupal\feeds\Feeds\Target\Path', 'prepareTarget')->getClosure();

    $configuration = [
      'importer' => $this->getMock('Drupal\feeds\ImporterInterface'),
      'target_definition' =>  $method($this->getMockFieldDefinition()),
    ];
    $target = new Path($configuration, 'path', []);

    $method = $this->getProtectedClosure($target, 'prepareValue');

    $values = ['alias' => 'path '];
    $method(0, $values);
    $this->assertSame($values['alias'], 'path');
  }

}
