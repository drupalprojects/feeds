<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\ImporterListControllerTest.
 */

namespace Drupal\feeds\Tests;

use Drupal\feeds\Tests\FeedsUnitTestCase;

/**
 * @covers \Drupal\feeds\ImporterListController
 */
class ImporterListControllerTest extends FeedsUnitTestCase {

  protected $controller;

  protected $importer;


  public static function getInfo() {
    return array(
      'name' => 'Feeds: Importer list controller.',
      'description' => 'Tests the importer list controller.',
      'group' => 'Feeds',
    );
  }

  public function setUp() {
    $this->controller = $this->getMockBuilder('\Drupal\feeds\ImporterListController')
                             ->setMethods(array('buildOperations'))
                             ->disableOriginalConstructor()
                             ->getMock();

    $this->controller->setTranslationManager($this->getStringTranslationStub());
    $this->importer = $this->getMockImporter();
  }

  public function testBuildRow() {
    $row = $this->controller->buildRow($this->importer);
    $this->assertSame('Test importer', $row['label']);
  }

  public function testBuildHeader() {
    $header = $this->controller->buildHeader($this->importer);
    $this->assertSame('Label', $header['label']);
  }

}
