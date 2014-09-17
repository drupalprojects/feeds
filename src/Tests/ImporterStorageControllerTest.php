<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\ImporterStorageControllerTest.
 */

namespace Drupal\feeds\Tests;

use Drupal\Core\DependencyInjection\Container;
use Drupal\Tests\UnitTestCase;
use Drupal\feeds\ImporterStorageController;

/**
 * @covers \Drupal\feeds\ImporterStorageController
 */
class ImporterStorageControllerTest extends UnitTestCase {


  public static function getInfo() {
    return array(
      'name' => 'Feeds: Importer storage controller',
      'description' => 'Tests the importer storage controller.',
      'group' => 'Feeds',
    );
  }

  public function test() {
    $entityType = $this->getMock('\Drupal\Core\Entity\EntityTypeInterface');
    $entityType->expects($this->once())
               ->method('id')
               ->will($this->returnValue('feeds_importer'));
    $configFactory = $this->getMockBuilder('\Drupal\Core\Config\ConfigFactory')
                          ->disableOriginalConstructor()
                          ->getMock();
    $storage = $this->getMock('\Drupal\Core\Config\StorageInterface');
    $queryFactory = $this->getMockBuilder('\Drupal\Core\Entity\Query\QueryFactory')
                         ->disableOriginalConstructor()
                         ->getMock();

    $query = $this->getMock('\Drupal\Core\Entity\Query\QueryInterface');

    $queryFactory->expects($this->any())
                 ->method('get')
                 ->will($this->returnValue($query));

    $uuid = $this->getMock('\Drupal\Component\Uuid\UuidInterface');

    $container = $this->getMockBuilder('\Drupal\Core\DependencyInjection\Container')
                      ->disableOriginalConstructor()
                      ->getMock();
    $map = array(
      array('entity.query', Container::EXCEPTION_ON_INVALID_REFERENCE, $queryFactory),
    );

    $container->expects($this->any())
                    ->method('get')
                    ->will($this->returnValueMap($map));

    \Drupal::setContainer($container);

    $controller = new ImporterStorageController($entityType, $configFactory, $storage, $queryFactory, $uuid);
    $this->assertSame(array(), $controller->loadEnabled());
  }

}
