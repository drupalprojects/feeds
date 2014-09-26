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
    $method = $this->getMethod('Drupal\feeds\Feeds\Target\String', 'prepareTarget')->getClosure();
    $field_definition = $this->getMockFieldDefinition(['max_length' => 5]);
    $field_definition->expects($this->any())
      ->method('getType')
      ->will($this->returnValue('string'));
    $configuration = [
      'importer' => $this->getMock('Drupal\feeds\ImporterInterface'),
      'target_definition' =>  $method($field_definition),
    ];
    $target = new String($configuration, 'link', []);

    $method = $this->getProtectedClosure($target, 'prepareValue');

    $values = ['value' => 'longstring'];
    $method(0, $values);
    $this->assertSame($values['value'], 'longs');
  }

}
