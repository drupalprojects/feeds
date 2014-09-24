<?php

/**
 * @file
 * Contains \Drupal\feeds\Element\Uri.
 */

namespace Drupal\feeds\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Url;

/**
 * Provides a form element for input of a URI.
 *
 * @FormElement("feeds_uri")
 */
class Uri extends Url {

  /**
   * Form element validation handler for #type 'url'.
   *
   * Note that #maxlength and #required is validated by _form_validate() already.
   */
  public static function validateUrl(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = trim($element['#value']);
    $form_state->setValueForElement($element, $value);

    if (!$value) {
      return;
    }

    $parsed = parse_url($value);
    $valid = $parsed && isset($parsed['scheme']) && isset($parsed['host']);

    if (!$valid) {
      $form_state->setError($element, t('The URI %url is not valid.', array('%url' => $value)));
    }
  }

}
