<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Feeds\Target\EmailTest.
 */

namespace Drupal\feeds\Tests\Feeds\Target;

use Drupal\feeds\Feeds\Target\Email;
use Drupal\feeds\Tests\FeedsUnitTestCase;

/**
 * @covers \Drupal\feeds\Feeds\Target\Email
 */
class EmailTest extends FeedsUnitTestCase {

  public function test() {
    $method = $this->getMethod('Drupal\feeds\Feeds\Target\Email', 'prepareTarget')->getClosure();

    $configuration = [
      'importer' => $this->getMock('Drupal\feeds\ImporterInterface'),
      'target_definition' =>  $method($this->getMockFieldDefinition()),
    ];
    $target = new Email($configuration, 'email', []);

    $method = $this->getProtectedClosure($target, 'prepareValue');

    $values = ['value' => 'string'];
    $method(0, $values);
    $this->assertSame($values['value'], '');

    $values = ['value' => 'admin@example.com'];
    $method(0, $values);
    $this->assertSame($values['value'], 'admin@example.com');
  }

}
