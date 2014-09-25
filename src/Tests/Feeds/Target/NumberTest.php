<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Feeds\Target\NumberTest.
 */

namespace Drupal\feeds\Tests\Feeds\Target;

use Drupal\feeds\Feeds\Target\Number;
use Drupal\feeds\Tests\FeedsUnitTestCase;

/**
 * @covers \Drupal\feeds\Feeds\Target\Number
 */
class NumberTest extends FeedsUnitTestCase {

  public function testPrepareValue() {
    $importer = $this->getMock('Drupal\feeds\ImporterInterface');
    $target = new Number(['importer' => $importer], 'link', []);

    $method = $this->getProtectedClosure($target, 'prepareValue');

    $values = ['value' => 'string'];
    $method(0, $values);
    $this->assertSame($values['value'], '');

    $values = ['value' => '10'];
    $method(0, $values);
    $this->assertSame($values['value'], '10');
  }

}
