<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Feeds\Target\StringTest.
 */

namespace Drupal\feeds\Tests\Feeds\Target;

use Drupal\feeds\Feeds\Target\String;
use Drupal\feeds\Tests\FeedsUnitTestCase;

/**
 * @covers \Drupal\feeds\Feeds\Target\String
 */
class StringTest extends FeedsUnitTestCase {

  public function testPrepareValue() {
    $importer = $this->getMock('Drupal\feeds\ImporterInterface');
    $settings = [
      'importer' => $importer,
      'settings' => [
        'max_length' => 5,
      ],
    ];
    $target = new String($settings, 'link', []);

    $method = $this->getProtectedClosure($target, 'prepareValue');

    $values = ['value' => 'longstring'];
    $method(0, $values);
    $this->assertSame($values['value'], 'longs');
  }

  public function testPrepareTarget() {
    $method = $this->getMethod('Drupal\feeds\Feeds\Target\String', 'prepareTarget')->getClosure();
    $targets = [];
    $method($targets);
    $this->assertTrue($targets['unique']['value']);
  }

}
