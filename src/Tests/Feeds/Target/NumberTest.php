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
    $method = $this->getMethod('Drupal\feeds\Feeds\Target\Number', 'prepareTarget')->getClosure();

    $configuration = [
      'feed_type' => $this->getMock('Drupal\feeds\FeedTypeInterface'),
      'target_definition' =>  $method($this->getMockFieldDefinition()),
    ];
    $target = new Number($configuration, 'link', []);

    $method = $this->getProtectedClosure($target, 'prepareValue');

    $values = ['value' => 'string'];
    $method(0, $values);
    $this->assertSame($values['value'], '');

    $values = ['value' => '10'];
    $method(0, $values);
    $this->assertSame($values['value'], '10');
  }

}
