<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Feeds\Target\UriTest.
 */

namespace Drupal\feeds\Tests\Feeds\Target;

use Drupal\feeds\Feeds\Target\Uri;
use Drupal\feeds\Tests\FeedsUnitTestCase;

/**
 * @covers \Drupal\feeds\Feeds\Target\Uri
 */
class UriTest extends FeedsUnitTestCase {

  public function testPrepareValue() {
    $method = $this->getMethod('Drupal\feeds\Feeds\Target\Uri', 'prepareTarget')->getClosure();
    $method($this->getMockFieldDefinition());
  }

}
