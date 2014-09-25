<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Feeds\Target\LinkTest.
 */

namespace Drupal\feeds\Tests\Feeds\Target;

use Drupal\feeds\Feeds\Target\Link;
use Drupal\feeds\Tests\FeedsUnitTestCase;

/**
 * @covers \Drupal\feeds\Feeds\Target\Link
 */
class LinkTest extends FeedsUnitTestCase {

  public function testPrepareValue() {
    $importer = $this->getMock('Drupal\feeds\ImporterInterface');
    $target = new Link(['importer' => $importer], 'link', []);

    $method = $this->getProtectedClosure($target, 'prepareValue');

    $values = ['url' => 'string'];
    $method(0, $values);
    $this->assertSame($values['url'], '');

    $values = ['url' => 'http://example.com'];
    $method(0, $values);
    $this->assertSame($values['url'], 'http://example.com');
  }

  public function testPrepareTarget() {
    $method = $this->getMethod('Drupal\feeds\Feeds\Target\Link', 'prepareTarget')->getClosure();
    $targets = [
      'properties' => [
        'attributes' => [],
      ],
    ];
    $method($targets);
    $this->assertSame($targets, ['properties' => []]);
  }

}
