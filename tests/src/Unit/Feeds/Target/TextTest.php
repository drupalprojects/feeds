<?php

/**
 * @file
 * Contains \Drupal\Tests\feeds\Unit\Feeds\Target\TextTest.
 */

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\feeds\Feeds\Target\Text;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;

/**
 * Tests \Drupal\feeds\Feeds\Target\Text.
 *
 * @covers \Drupal\feeds\Feeds\Target\Text
 * @group Feeds
 */
class TextTest extends FeedsUnitTestCase {

  protected $target;

  public function setUp() {
    parent::setUp();

    $method = $this->getMethod('Drupal\feeds\Feeds\Target\Text', 'prepareTarget')->getClosure();
    $configuration = [
      'feed_type' => $this->getMock('Drupal\feeds\FeedTypeInterface'),
      'target_definition' => $method($this->getMockFieldDefinition()),
    ];
    $this->target = new Text($configuration, 'text', [], $this->getMock('Drupal\Core\Session\AccountInterface'));
    $this->target->setStringTranslation($this->getStringTranslationStub());
  }

  public function test() {
    $method = $this->getProtectedClosure($this->target, 'prepareValue');

    $values = ['value' => 'longstring'];
    $method(0, $values);
    $this->assertSame($values['value'], 'longstring');
    $this->assertSame($values['format'], 'plain_text');
  }

  public function testBuildConfigurationForm() {
    $form_state = new FormState();
    $form = $this->target->buildConfigurationForm([], $form_state);
    $this->assertSame(count($form), 1);
  }

  public function testSummary() {
    $storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
    $storage->expects($this->any())
      ->method('loadByProperties')
      ->with(['status' => '1', 'format' => 'plain_text'])
      ->will($this->onConsecutiveCalls([new \FeedsFilterStub('Test filter')], []));

    $manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $manager->expects($this->exactly(2))
      ->method('getStorage')
      ->will($this->returnValue($storage));

    $container = new ContainerBuilder();
    $container->set('entity.manager', $manager);
    \Drupal::setContainer($container);

    $this->assertSame($this->target->getSummary(), 'Format: <em class="placeholder">Test filter</em>');
    $this->assertEquals($this->target->getSummary(), '');
  }

}

