<?php

/**
 * @file
 * Contains \Drupal\Tests\feeds\Unit\Feeds\Target\StringTest.
 */

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\feeds\Feeds\Target\String;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;

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
      'feed_type' => $this->getMock('Drupal\feeds\FeedTypeInterface'),
      'target_definition' =>  $method($field_definition),
    ];
    $target = new String($configuration, 'link', []);

    $method = $this->getProtectedClosure($target, 'prepareValue');

    $values = ['value' => 'longstring'];
    $method(0, $values);
    $this->assertSame($values['value'], 'longs');
  }

}
