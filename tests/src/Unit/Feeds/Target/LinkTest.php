<?php

/**
 * @file
 * Contains \Drupal\Tests\feeds\Unit\Feeds\Target\LinkTest.
 */

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\feeds\Feeds\Target\Link;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;

/**
 * @covers \Drupal\feeds\Feeds\Target\Link
 */
class LinkTest extends FeedsUnitTestCase {

  public function testPrepareValue() {
    $method = $this->getMethod('Drupal\feeds\Feeds\Target\Link', 'prepareTarget')->getClosure();

    $configuration = [
      'feed_type' => $this->getMock('Drupal\feeds\FeedTypeInterface'),
      'target_definition' =>  $method($this->getMockFieldDefinition()),
    ];
    $target = new Link($configuration, 'link', []);

    $method = $this->getProtectedClosure($target, 'prepareValue');

    $values = ['url' => 'string'];
    $method(0, $values);
    $this->assertSame($values['url'], '');

    $values = ['url' => 'http://example.com'];
    $method(0, $values);
    $this->assertSame($values['url'], 'http://example.com');
  }

  // public function testPrepareTarget() {
  //   $method = $this->getMethod('Drupal\feeds\Feeds\Target\Link', 'prepareTarget')->getClosure();
  //   $targets = [
  //     'properties' => [
  //       'attributes' => [],
  //     ],
  //   ];
  //   $method($targets);
  //   $this->assertSame($targets, ['properties' => []]);
  // }

}
