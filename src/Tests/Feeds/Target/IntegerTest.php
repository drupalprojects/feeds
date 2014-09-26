<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Feeds\Target\IntegerTest.
 */

namespace Drupal\feeds\Tests\Feeds\Target;

use Drupal\feeds\Feeds\Target\Integer;
use Drupal\feeds\Tests\FeedsUnitTestCase;

/**
 * @covers \Drupal\feeds\Feeds\Target\Integer
 */
class IntegerTest extends FeedsUnitTestCase {

  public function testPrepareValue() {
    $method = $this->getMethod('Drupal\feeds\Feeds\Target\Integer', 'prepareTarget')->getClosure();

    $configuration = [
      'importer' => $this->getMock('Drupal\feeds\ImporterInterface'),
      'target_definition' =>  $method($this->getMockFieldDefinition()),
    ];
    $target = new Integer($configuration, 'link', []);

    $method = $this->getProtectedClosure($target, 'prepareValue');

    $values = ['value' => 'string'];
    $method(0, $values);
    $this->assertSame($values['value'], '');

    $values = ['value' => '10'];
    $method(0, $values);
    $this->assertSame($values['value'], 10);
  }

}
