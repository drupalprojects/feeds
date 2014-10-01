<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Feeds\Target\FileTest.
 */

namespace Drupal\feeds\Tests\Feeds\Target;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\feeds\Feeds\Target\File;
use Drupal\feeds\Tests\FeedsUnitTestCase;

/**
 * @covers \Drupal\feeds\Feeds\Target\File
 */
class FileTest extends FeedsUnitTestCase {

  protected $container;
  protected $importer;
  protected $targetDefinition;

  public function setUp() {
    parent::setUp();

    $this->importer = $this->getMock('Drupal\feeds\ImporterInterface');

    $method = $this->getMethod('Drupal\feeds\Feeds\Target\File', 'prepareTarget')->getClosure();
    $this->targetDefinition = $method($this->getMockFieldDefinition());
  }

  public function test() {
    // $configuration = [
    //   'importer' => $this->importer,
    //   'target_definition' => $this->targetDefinition,
    // ];

    // $target = File::create($this->container, $configuration, 'text', []);

    // $method = $this->getProtectedClosure($target, 'prepareValue');

    // $values = ['value' => 'longstring'];
    // $method(0, $values);
    // $this->assertSame($values['value'], 'longstring');
    // $this->assertSame($values['format'], 'plain_text');
  }
}

