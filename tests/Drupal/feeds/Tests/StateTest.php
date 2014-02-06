<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\StateTest.
 */

namespace Drupal\feeds\Tests;

use Drupal\Tests\UnitTestCase;
use Drupal\feeds\State;
use Drupal\feeds\StateInterface;

/**
 * @covers \Drupal\feeds\State
 */
class StateTest extends UnitTestCase {


  public static function getInfo() {
    return array(
      'name' => 'Feeds: State',
      'description' => 'Tests the state holder.',
      'group' => 'Feeds',
    );
  }

  public function testProgress() {
    $state = new State();
    $state->progress(10, 10);
    $this->assertSame(StateInterface::BATCH_COMPLETE, $state->progress);

    $state->progress(20, 10);
    $this->assertSame(0.5, $state->progress);

    $state->progress(10, 30);
    $this->assertSame(StateInterface::BATCH_COMPLETE, $state->progress);

    $state->progress(0, 0);
    $this->assertSame(StateInterface::BATCH_COMPLETE, $state->progress);

    $state->progress(PHP_INT_MAX, PHP_INT_MAX - 1);
    $this->assertSame(.99, $state->progress);
  }

}
