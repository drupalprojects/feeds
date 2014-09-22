<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\FeedsUnitTestCase.
 */

namespace Drupal\feeds\Tests;

use Drupal\Tests\UnitTestCase;

/**
 * Tests the importer list controller.
 */
abstract class FeedsUnitTestCase extends UnitTestCase {

  public function setUp() {
    parent::setUp();
    if (!defined('WATCHDOG_NOTICE')) {
      define('WATCHDOG_NOTICE', 5);
    }
    if (!defined('WATCHDOG_INFO')) {
      define('WATCHDOG_INFO', 6);
    }
    $this->cleanUpFiles();
  }

  public function tearDown() {
    $this->cleanUpFiles();
    parent::tearDown();
  }

  protected function getMockImporter() {
    $importer = $this->getMock('\Drupal\feeds\ImporterInterface');
    $importer->id = 'test_importer';
    $importer->description = 'This is a test importer';
    $importer->label = 'Test importer';
    $importer->expects($this->any())
             ->method('label')
             ->will($this->returnValue($importer->label));
    return $importer;
  }

  protected static function getMethod($class, $name) {
    $class = new \ReflectionClass($class);
    $method = $class->getMethod($name);
    $method->setAccessible(TRUE);
    return $method;
  }

  protected function getMockAccount(array $perms = array()) {
    $account = $this->getMock('\Drupal\Core\Session\AccountInterface');
    if ($perms) {
      $map = array();
      foreach ($perms as $perm => $has) {
        $map[] = array($perm, $has);
      }
      $account->expects($this->any())
              ->method('hasPermission')
              ->will($this->returnValueMap($map));
    }

    return $account;
  }

  /**
   * Removes files and directories based on class constants.
   */
  protected function cleanUpFiles() {

    $refl = new \ReflectionClass(get_class($this));
    $files = array_flip($refl->getConstants());

    $files = array_filter($files, function ($constant_name) {
      return strpos($constant_name, 'FILE') === 0 || strpos($constant_name, 'DIRECTORY') === 0;
    });

    // Remove files first so directories will be empty.
    foreach ($files as $file => $name) {
      if (is_file($file)) {
        unset($files[$name]);
        unlink($file);
      }
    }

    foreach (array_keys($files) as $directory) {
      if (is_dir($directory)) {
        rmdir($directory);
      }
    }
  }

}
