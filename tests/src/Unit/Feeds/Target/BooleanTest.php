<?php

/**
 * @file
 * Contains \Drupal\Tests\feeds\Unit\Feeds\Target\BooleanTest.
 */

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\feeds\Feeds\Target\Boolean;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\Boolean
 * @group feeds
 */
class BooleanTest extends FeedsUnitTestCase {

  public function test() {
    $method = $this->getMethod('Drupal\feeds\Feeds\Target\Boolean', 'prepareTarget')->getClosure();

    $configuration = [
      'feed_type' => $this->getMock('Drupal\feeds\FeedTypeInterface'),
      'target_definition' =>  $method($this->getMockFieldDefinition()),
    ];

    $target = new Boolean($configuration, 'boolean', []);
    $values = ['value' => 'string'];

    $method = $this->getProtectedClosure($target, 'prepareValue');
    $method(0, $values);
    $this->assertTrue($values['value']);
  }

}
