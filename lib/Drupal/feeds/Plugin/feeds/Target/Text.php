<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Target\Text.
 */

namespace Drupal\feeds\Plugin\feeds\Target;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\feeds\FeedsElement;
use Drupal\field\Entity\FieldInstance;
use Drupal\feeds\Plugin\FieldTargetBase;

/**
 * Defines a text field mapper.
 *
 * @Plugin(
 *   id = "text",
 *   title = @Translation("Text"),
 *   field_types = {"string_field", "list_text", "text", "text_long", "text_with_summary"}
 * )
 */
class Text extends FieldTargetBase {

  protected function prepareTarget(array &$target) {
    unset($target['properties']['format']);
  }

  public function getSummary() {
    return 'asdfasf';
  }

}
