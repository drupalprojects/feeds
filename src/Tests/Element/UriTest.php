<?php

/**
 * @file
 * Contains Drupal\feeds\Tests\Element\UriTest.
 */

namespace Drupal\feeds\Tests\Element {

use Drupal\Core\Form\FormState;
use Drupal\feeds\Element\Uri;
use Drupal\feeds\Tests\FeedsUnitTestCase;

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

    $element = ['#value' => ' public://test', '#parents' => ['element']];
    Uri::validateUrl($element, $form_state, $complete_form);
    $this->assertSame($form_state->getValue('element'), 'public://test');

    $element = ['#value' => '', '#parents' => ['element']];
    Uri::validateUrl($element, $form_state, $complete_form);
    $this->assertSame($form_state->getValue('element'), '');

    $element = ['#value' => '@@', '#parents' => ['element']];
    Uri::validateUrl($element, $form_state, $complete_form);
    $this->assertSame($form_state->getValue('element'), '@@');
    $this->assertSame($form_state->getError($element), 'The URI <em class="placeholder">@@</em> is not valid.');
  }

}
}

namespace {
  use Drupal\Component\Utility\String;

  if (!function_exists('t')) {
    function t($string, array $args = array()) {
      return String::format($string, $args);
    }
  }

  if (!function_exists('drupal_set_message')) {
    function drupal_set_message() {}
  }
}
