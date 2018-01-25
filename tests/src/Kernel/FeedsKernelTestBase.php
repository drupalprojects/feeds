<?php

namespace Drupal\Tests\feeds\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Provides a base class for Feeds kernel tests.
 */
abstract class FeedsKernelTestBase extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['feeds'];

}
