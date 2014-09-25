<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\FeedAccessControllerTest.
 */

namespace Drupal\feeds\Tests;

use Drupal\Core\Language\Language;
use Drupal\Core\Session\AccountInterface;
use Drupal\feeds\FeedAccessController;
use Drupal\feeds\StateInterface;
use Drupal\feeds\Tests\FeedsUnitTestCase;

/**
 * @covers \Drupal\feeds\FeedAccessController
 * @group Feeds
 */
class FeedAccessControllerTest extends FeedsUnitTestCase {

  protected $entityType;

  protected $controller;

  protected $moduleHandler;

  public function setUp() {
    parent::setUp();
    $this->entityType = $this->getMock('\Drupal\Core\Entity\EntityTypeInterface');
    $this->entityType->expects($this->once())
                ->method('id')
                ->will($this->returnValue('feeds_feed'));
    $this->controller = new FeedAccessController($this->entityType);
    $this->moduleHandler = $this->getMock('\Drupal\Core\Extension\ModuleHandlerInterface');
    $this->moduleHandler->expects($this->any())
                   ->method('invokeAll')
                   ->will($this->returnValue(array()));
    $this->controller->setModuleHandler($this->moduleHandler);
  }

  public function test() {
    $feed = $this->getMockBuilder('\Drupal\feeds\FeedInterface')
                 ->disableOriginalConstructor()
                 ->getMock();
    $feed->expects($this->any())
         ->method('bundle')
         ->will($this->returnValue('feed_bundle'));

    $account = $this->getMock('\Drupal\Core\Session\AccountInterface');

    $this->assertFalse($this->controller->access($feed, 'beep', Language::LANGCODE_DEFAULT, $account));
    $this->assertFalse($this->controller->access($feed, 'unlock', Language::LANGCODE_DEFAULT, $account));

    $feed->expects($this->any())
         ->method('progressImporting')
         ->will($this->returnValue(StateInterface::BATCH_COMPLETE));
    $feed->expects($this->any())
         ->method('progressClearing')
         ->will($this->returnValue(StateInterface::BATCH_COMPLETE));

    $this->controller->resetCache();

    $this->assertFalse($this->controller->access($feed, 'unlock', Language::LANGCODE_DEFAULT, $account));

    $account->expects($this->any())
            ->method('hasPermission')
            ->with($this->equalTo('administer feeds'))
            ->will($this->returnValue(TRUE));

    $this->assertTrue($this->controller->access($feed, 'clear', Language::LANGCODE_DEFAULT, $account));

    $account = $this->getMock('\Drupal\Core\Session\AccountInterface');

    $account->expects($this->exactly(2))
            ->method('hasPermission')
            ->with($this->logicalOr(
                 $this->equalTo('administer feeds'),
                 $this->equalTo('delete feed_bundle feeds')
             ))
            ->will($this->onConsecutiveCalls(FALSE, TRUE));
    $this->assertTrue($this->controller->access($feed, 'delete', Language::LANGCODE_DEFAULT, $account));
  }

  public function testCheckCreateAccess() {
    $account = $this->getMock('\Drupal\Core\Session\AccountInterface');

    $account->expects($this->exactly(2))
            ->method('hasPermission')
            ->with($this->logicalOr(
                 $this->equalTo('administer feeds'),
                 $this->equalTo('create feed_bundle feeds')
             ))
            ->will($this->onConsecutiveCalls(FALSE, FALSE));
    $this->assertFalse($this->controller->createAccess('feed_bundle', $account));

    $this->controller->resetCache();

    $account = $this->getMock('\Drupal\Core\Session\AccountInterface');
    $account->expects($this->exactly(2))
            ->method('hasPermission')
            ->with($this->logicalOr(
                 $this->equalTo('administer feeds'),
                 $this->equalTo('create feed_bundle feeds')
             ))
            ->will($this->onConsecutiveCalls(FALSE, TRUE));
    $this->assertTrue($this->controller->createAccess('feed_bundle', $account));
  }

}
