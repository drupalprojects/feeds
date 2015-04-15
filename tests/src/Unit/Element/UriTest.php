<?php

/**
 * @file
 * Contains Drupal\Tests\feeds\Unit\Element\UriTest.
 */

namespace Drupal\Tests\feeds\Unit\Element {

use Drupal\Core\Form\FormState;
use Drupal\feeds\Element\Uri;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;

/**
 * @covers \Drupal\feeds\Element\Uri
 * @group Feeds
 */
class UriTest extends FeedsUnitTestCase {

  /**
   * Tests validation.
   */
  public function testValidation() {
    $complete_form = [];
    $form_state = new FormState();

    $element_object = new Uri([], '', []);

    $element = ['#value' => ' public://test', '#parents' => ['element']];
    $element += $element_object->getInfo();
    Uri::validateUrl($element, $form_state, $complete_form);
    $this->assertSame($form_state->getValue('element'), 'public://test');

    $element = ['#value' => '', '#parents' => ['element']];
    $element += $element_object->getInfo();
    Uri::validateUrl($element, $form_state, $complete_form);
    $this->assertSame($form_state->getValue('element'), '');

    $element = ['#value' => '@@', '#parents' => ['element']];
    $element += $element_object->getInfo();
    Uri::validateUrl($element, $form_state, $complete_form);
    $this->assertSame($form_state->getValue('element'), '@@');
    $this->assertSame($form_state->getError($element), 'The URI <em class="placeholder">@@</em> is not valid.');
    $form_state->clearErrors();

    $element = ['#value' => 'badscheme://foo', '#parents' => ['element'], '#allowed_schemes' => ['public']];
    $element += $element_object->getInfo();
    Uri::validateUrl($element, $form_state, $complete_form);
    $this->assertSame($form_state->getError($element), 'The scheme <em class="placeholder">badscheme</em> is invalid. Available schemes: public.');
  }

}
}

namespace {
  use Drupal\Component\Utility\String;

  if (!function_exists('t')) {
    function t($string, array $args = []) {
      return String::format($string, $args);
    }
  }

}
