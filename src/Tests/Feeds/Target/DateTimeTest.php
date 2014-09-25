<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Feeds\Target\DateTimeTest.
 */

namespace Drupal\feeds\Tests\Feeds\Target;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\feeds\Feeds\Target\DateTime;
use Drupal\feeds\Tests\FeedsUnitTestCase;

/**
 * @covers \Drupal\feeds\Feeds\Target\DateTime
 */
class DateTimeTest extends FeedsUnitTestCase {
  protected $importer;

  public function setUp() {
    require_once DRUPAL_ROOT . '/core/includes/common.inc';

    parent::setUp();

    $container = new ContainerBuilder();
    $language_manager = $this->getMock('Drupal\Core\Language\LanguageManagerInterface');
    $language_manager->expects($this->any())
      ->method('getCurrentLanguage')
      ->will($this->returnValue((object) ['id' => 'en']));
    $container->set('language_manager', $language_manager);

    \Drupal::setContainer($container);

    $this->importer = $this->getMock('Drupal\feeds\ImporterInterface');
  }

  public function test() {
    $settings = [
      'importer' => $this->importer,
      'settings' => [
        'datetime_type' => 'date',
      ],
    ];
    $target = new DateTime($settings, 'boolean', []);
    $method = $this->getProtectedClosure($target, 'prepareValue');

    $values = ['value' => 1411606273];
    $method(0, $values);
    $this->assertSame($values['value'], date(DATETIME_DATE_STORAGE_FORMAT, 1411606273));
  }

  public function testFromDateTime() {
    $settings = [
      'importer' => $this->importer,
      'settings' => [
        'datetime_type' => 'time',
      ],
    ];
    $target = new DateTime($settings, 'boolean', []);
    $method = $this->getProtectedClosure($target, 'prepareValue');

    $time = time();

    $values = ['value' => new \DateTime("@$time")];
    $method(0, $values);
    $this->assertSame($values['value'], date(DATETIME_DATETIME_STORAGE_FORMAT, $time));
  }

  public function testWithErrors() {
    $settings = [
      'importer' => $this->importer,
      'settings' => [
        'datetime_type' => 'time',
      ],
    ];
    $target = new DateTime($settings, 'boolean', []);
    $method = $this->getProtectedClosure($target, 'prepareValue');

    $time = time();

    $values = ['value' => '2000-05-32'];
    $method(0, $values);
    $this->assertSame($values['value'], '');
  }

  public function testYearValue() {
    $settings = [
      'importer' => $this->importer,
      'settings' => [
        'datetime_type' => 'time',
      ],
    ];
    $target = new DateTime($settings, 'boolean', []);
    $method = $this->getProtectedClosure($target, 'prepareValue');

    $time = time();

    $values = ['value' => '2000'];
    $method(0, $values);
    $this->assertSame($values['value'], '2000-01-01T00:00:00');
  }

}
