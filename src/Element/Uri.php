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
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $info['#allowed_schemes'] = [];
    return $info;
  }

  /**
   * Form element validation handler for #type 'feeds_uri'.
   */
  public static function validateUrl(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = file_stream_wrapper_uri_normalize(trim($element['#value']));
    $form_state->setValueForElement($element, $value);

    if (!$value) {
      return;
    }

    $parsed = parse_url($value);
    $valid = $parsed && isset($parsed['scheme']) && isset($parsed['host']);

    if (!$valid) {
      $form_state->setError($element, t('The URI %url is not valid.', ['%url' => $value]));
      return;
    }

    if ($element['#allowed_schemes'] && !in_array(file_uri_scheme($value), $element['#allowed_schemes'], TRUE)) {
      $args = [
        '%scheme' => file_uri_scheme($value),
        '@schemes' => implode(', ', $element['#allowed_schemes']),
      ];
      $form_state->setError($element, t("The scheme %scheme is invalid. Available schemes: @schemes.", $args));
    }
  }

}
