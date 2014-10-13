<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\FeedsUnitTestCase.
 */

namespace Drupal\feeds\Tests {

use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;

/**
 * Base class for Feeds unit tests.
 */
abstract class FeedsUnitTestCase extends UnitTestCase {

  public function setUp() {
    parent::setUp();

    $this->defineConstants();
    vfsStream::setup('feeds');
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

  protected function getMockFeed() {
    $feed = $this->getMock('Drupal\feeds\FeedInterface');
    return $feed;
  }

  protected function getMethod($class, $name) {
    $class = new \ReflectionClass($class);
    $method = $class->getMethod($name);
    $method->setAccessible(TRUE);
    return $method;
  }

  protected function getProtectedClosure($object, $method) {
    return $this->getMethod(get_class($object), $method)->getClosure($object);
  }

  protected function callProtectedMethod($object, $method, array $args = []) {
    $closure = $this->getProtectedClosure($object, $method);
    return call_user_func_array($closure, $args);
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

  protected function getMockFieldDefinition(array $settings = []) {
    $definition = $this->getMock('Drupal\Core\Field\FieldDefinitionInterface');
    $definition->expects($this->any())
      ->method('getSettings')
      ->will($this->returnValue($settings));
    return $definition;
  }

  /**
   * Defines stub constants.
   */
  protected function defineConstants() {
    if (!defined('WATCHDOG_ERROR')) {
      define('WATCHDOG_ERROR', 3);
    }
    if (!defined('WATCHDOG_WARNING')) {
      define('WATCHDOG_WARNING', 4);
    }
    if (!defined('WATCHDOG_NOTICE')) {
      define('WATCHDOG_NOTICE', 5);
    }
    if (!defined('WATCHDOG_INFO')) {
      define('WATCHDOG_INFO', 6);
    }

    if (!defined('STREAM_WRAPPERS_WRITE_VISIBLE')) {
      define('STREAM_WRAPPERS_WRITE_VISIBLE', 1);
    }

    if (!defined('DATETIME_STORAGE_TIMEZONE')) {
      define('DATETIME_STORAGE_TIMEZONE', 'UTC');
    }
    if (!defined('DATETIME_DATETIME_STORAGE_FORMAT')) {
      define('DATETIME_DATETIME_STORAGE_FORMAT', 'Y-m-d\TH:i:s');
    }
    if (!defined('DATETIME_DATE_STORAGE_FORMAT')) {
      define('DATETIME_DATE_STORAGE_FORMAT', 'Y-m-d');
    }

    if (!defined('FILE_MODIFY_PERMISSIONS')) {
      define('FILE_MODIFY_PERMISSIONS', 2);
    }
    if (!defined('FILE_CREATE_DIRECTORY')) {
      define('FILE_CREATE_DIRECTORY', 1);
    }
    if (!defined('FILE_EXISTS_REPLACE')) {
      define('FILE_EXISTS_REPLACE', 1);
    }
  }

}
}

namespace {
  use Drupal\Core\Session\AccountInterface;
  use Drupal\Component\Utility\String;

  if (!function_exists('t')) {
    function t($string, array $args = array()) {
      return String::format($string, $args);
    }
  }

  if (!function_exists('drupal_set_message')) {
    function drupal_set_message() {}
  }

  if (!function_exists('filter_formats')) {
    function filter_formats(AccountInterface $account) {
      return array('test_format' => new FeedsFilterStub('Test format'));
    }
  }

  if (!function_exists('file_stream_wrapper_uri_normalize')) {
    function file_stream_wrapper_uri_normalize($dir) {
      return $dir;
    }
  }
  if (!function_exists('file_get_stream_wrappers')) {
    function file_get_stream_wrappers() {
      return [
        'vfs' => ['description' => 'VFS'],
        'public' => ['description' => 'Public'],
      ];
    }
  }
  if (!function_exists('file_uri_scheme')) {
    function file_uri_scheme($uri) {
      $position = strpos($uri, '://');
      return $position ? substr($uri, 0, $position) : FALSE;
    }
  }

  if (!function_exists('drupal_tempnam')) {
    function drupal_tempnam($scheme, $dir) {
      mkdir('vfs://feeds/' . $dir);
      $file = 'vfs://feeds/' . $dir . '/' . mt_rand(10, 1000);
      touch($file);
      return $file;
    }
  }

  if (!function_exists('file_prepare_directory')) {
    function file_prepare_directory(&$directory) {
      return mkdir($directory);
    }
  }

  if (!function_exists('file_unmanaged_move')) {
    function file_unmanaged_move($old, $new) {
      rename($old, $new);
    }
  }

  if (!function_exists('watchdog')) {
    function watchdog() {}
  }

  if (!function_exists('file_unmanaged_delete')) {
    function file_unmanaged_delete() {}
  }

  if (!function_exists('drupal_get_user_timezone')) {
    function drupal_get_user_timezone() {
      return 'UTC';
    }
  }

  if (!function_exists('batch_set')) {
    function batch_set() {}
  }

  class FeedsFilterStub {
    public function __construct($label) {
      $this->label = $label;
    }

    public function label() {
      return $this->label;
    }

  }
}
