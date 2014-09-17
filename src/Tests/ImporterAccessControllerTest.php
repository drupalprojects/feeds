<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\ImporterAccessControllerTest.
 */

namespace Drupal\feeds\Tests;

use Drupal\feeds\Tests\FeedsUnitTestCase;

/**
 * @covers \Drupal\feeds\ImporterAccessController
 */
class ImporterAccessControllerTest extends FeedsUnitTestCase {

  protected $controller;

  protected $importer;

  public static function getInfo() {
    return array(
      'name' => 'Feeds: Importer access controller.',
      'description' => 'Tests the access controller.',
      'group' => 'Feeds',
    );
  }

  public function setUp() {
    $this->controller = $this->getMockBuilder('\Drupal\feeds\ImporterAccessController')
                             ->setMethods(NULL)
                             ->disableOriginalConstructor()
                             ->getMock();
  }

  public function testCheckAccess() {
    $method = $this->getMethod('\Drupal\feeds\ImporterAccessController', 'checkAccess');

    $account = $this->getMockAccount(array('administer feeds' => TRUE));
    $this->assertTrue($method->invoke($this->controller, $this->getMockImporter(), 'op', 'language', $account));

    $account = $this->getMockAccount(array('administer feeds' => FALSE));
    $this->assertFalse($method->invoke($this->controller, $this->getMockImporter(), 'op', 'language', $account));
  }

  public function testCheckCreateAccess() {
    $method = $this->getMethod('\Drupal\feeds\ImporterAccessController', 'checkCreateAccess');

    $account = $this->getMockAccount(array('administer feeds' => TRUE));
    $this->assertTrue($method->invoke($this->controller, $account, array()));

    $account = $this->getMockAccount(array('administer feeds' => FALSE));
    $this->assertFalse($method->invoke($this->controller, $account, array()));
  }

}
