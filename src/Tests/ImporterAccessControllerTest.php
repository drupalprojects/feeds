<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\ImporterAccessControllerTest.
 */

namespace Drupal\feeds\Tests;

/**
 * @covers \Drupal\feeds\ImporterAccessController
 * @group Feeds
 */
class ImporterAccessControllerTest extends FeedsUnitTestCase {

  public function setUp() {
    parent::setUp();

    $this->entity = $this->getMock('Drupal\Core\Entity\EntityInterface');
    $this->account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->account->expects($this->once())
      ->method('hasPermission')
      ->with('administer feeds')
      ->will($this->returnValue(TRUE));
    $this->controller = $this->getMock('Drupal\feeds\ImporterAccessController', NULL, [], '', FALSE);
  }

  public function testCheckAccessTrue() {
    $method = $this->getMethod('Drupal\feeds\ImporterAccessController', 'checkAccess');
    $result = $method->invokeArgs($this->controller, [$this->entity, '', '', $this->account]);
    $this->assertTrue($result->isAllowed());
  }

  public function testCheckCreateAccessTrue() {
    $method = $this->getMethod('Drupal\feeds\ImporterAccessController', 'checkCreateAccess');
    $result = $method->invokeArgs($this->controller, [$this->account, [], NULL]);
    $this->assertTrue($result->isAllowed());
  }

}
