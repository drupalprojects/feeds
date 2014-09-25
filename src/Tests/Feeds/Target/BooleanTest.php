<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Feeds\Target\BooleanTest.
 */

namespace Drupal\feeds\Tests\Feeds\Target;

use Drupal\feeds\Feeds\Target\Boolean;
use Drupal\feeds\Tests\FeedsUnitTestCase;

/**
 * @covers \Drupal\feeds\Feeds\Target\Boolean
 */
class BooleanTest extends FeedsUnitTestCase {

  public function test() {
    $importer = $this->getMock('Drupal\feeds\ImporterInterface');
    $target = new Boolean(['importer' => $importer], 'boolean', []);
    $values = ['value' => 'string'];

    $method = $this->getProtectedClosure($target, 'prepareValue');
    $method(0, $values);
    $this->assertTrue($values['value']);
  }

}
