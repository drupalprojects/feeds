<?php

/**
 * @file
 * Contains \Drupal\Tests\feeds\Unit\Feeds\Target\UriTest.
 */

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\feeds\Feeds\Target\Uri;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;

/**
 * Tests \Drupal\feeds\Feeds\Target\Uri.
 *
 * @covers \Drupal\feeds\Feeds\Target\Uri
 * @group Feeds
 */
class UriTest extends FeedsUnitTestCase {

  public function testPrepareValue() {
    $method = $this->getMethod('Drupal\feeds\Feeds\Target\Uri', 'prepareTarget')->getClosure();
    $method($this->getMockFieldDefinition());
  }

}
