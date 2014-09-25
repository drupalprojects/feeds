<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Feeds\Target\TextTest.
 */

namespace Drupal\feeds\Tests\Feeds\Target;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\feeds\Feeds\Target\Text;
use Drupal\feeds\Tests\FeedsUnitTestCase;

/**
 * @covers \Drupal\feeds\Feeds\Target\Text
 */
class TextTest extends FeedsUnitTestCase {

  public function setUp() {
    parent::setUp();

    $this->container = new ContainerBuilder();
    $this->container->set('current_user', $this->getMock('Drupal\Core\Session\AccountInterface'));
    $this->importer = $this->getMock('Drupal\feeds\ImporterInterface');
  }

  public function test() {
    $settings = [
      'importer' => $this->importer,
      'settings' => ['max_length' => 5],
    ];
    $target = Text::create($this->container, $settings, 'text', []);

    $method = $this->getProtectedClosure($target, 'prepareValue');

    $values = ['value' => 'longstring'];
    $method(0, $values);
    $this->assertSame($values['value'], 'longs');
    $this->assertSame($values['format'], 'plain_text');
  }

  public function testAllowedValues() {
    $settings = [
      'importer' => $this->importer,
      'settings' => ['allowed_values' => ['key' => 'search value']],
    ];
    $target = Text::create($this->container, $settings, 'text', []);

    $method = $this->getProtectedClosure($target, 'prepareValue');

    $values = ['value' => 'search value'];
    $method(0, $values);
    $this->assertSame($values['value'], 'key');
    $this->assertSame($values['format'], 'plain_text');

    $values = ['value' => 'non value'];
    $method(0, $values);
    $this->assertSame($values['value'], '');
  }

  public function testBuildConfigurationForm() {
    $settings = ['importer' => $this->importer];
    $target = Text::create($this->container, $settings, 'text', []);
    $target->setStringTranslation($this->getStringTranslationStub());

    $form_state = new FormState();
    $form = $target->buildConfigurationForm([], $form_state);
    $this->assertSame(count($form), 1);
  }

  public function testSummary() {
    $settings = ['importer' => $this->importer];
    $target = Text::create($this->container, $settings, 'text', []);
    $target->setStringTranslation($this->getStringTranslationStub());


    $storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
    $storage->expects($this->any())
      ->method('loadByProperties')
      ->with(['status' => '1', 'format' => 'plain_text'])
      ->will($this->onConsecutiveCalls([new \FeedsFilterStub('Test filter')], []));

    $manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $manager->expects($this->exactly(2))
      ->method('getStorage')
      ->will($this->returnValue($storage));
    $this->container->set('entity.manager', $manager);
    \Drupal::setContainer($this->container);

    $this->assertSame($target->getSummary(), 'Format: <em class="placeholder">Test filter</em>');
    $this->assertEquals($target->getSummary(), '');
  }

  public function testPrepareTarget() {
    $method = $this->getMethod('Drupal\feeds\Feeds\Target\Text', 'prepareTarget')->getClosure();
    $targets = [
      'properties' => [
        'format' => [],
        'processed' => [],
        'summary_processed' => [],
      ],
    ];
    $method($targets);
    $this->assertSame($targets, ['properties' => []]);
  }

}

