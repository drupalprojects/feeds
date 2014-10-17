<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\ImporterAccessControlHandlerTest.
 */

namespace Drupal\feeds\Tests;

/**
 * @covers \Drupal\feeds\ImporterAccessControlHandler
 * @group Feeds
 */
class ImporterAccessControlHandlerTest extends FeedsUnitTestCase {

  public function setUp() {
    parent::setUp();

    $this->entity = $this->getMock('Drupal\Core\Entity\EntityInterface');
    $this->account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->account->expects($this->once())
      ->method('hasPermission')
      ->with('administer feeds')
      ->will($this->returnValue(TRUE));
    $this->controller = $this->getMock('Drupal\feeds\ImporterAccessControlHandler', NULL, [], '', FALSE);
  }

  public function testCheckAccessTrue() {
    $method = $this->getMethod('Drupal\feeds\ImporterAccessControlHandler', 'checkAccess');
    $result = $method->invokeArgs($this->controller, [$this->entity, '', '', $this->account]);
    $this->assertTrue($result->isAllowed());
  }

}
