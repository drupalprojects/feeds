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
    $method = $this->getMethod('Drupal\feeds\Feeds\Target\Boolean', 'prepareTarget')->getClosure();

    $configuration = [
      'importer' => $this->getMock('Drupal\feeds\ImporterInterface'),
      'target_definition' =>  $method($this->getMockFieldDefinition()),
    ];

    $target = new Boolean($configuration, 'boolean', []);
    $values = ['value' => 'string'];

    $method = $this->getProtectedClosure($target, 'prepareValue');
    $method(0, $values);
    $this->assertTrue($values['value']);
  }

}
