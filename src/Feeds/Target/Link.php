<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Target\Link.
 */

namespace Drupal\feeds\Feeds\Target;

use Drupal\Component\Utility\UrlHelper;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a link field mapper.
 *
 * @Plugin(
 *   id = "link",
 *   field_types = {"field_item:link"}
 * )
 */
class Link extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(array &$target) {
    unset($target['properties']['attributes']);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    $value = trim($values['url']);
    $values['url'] = UrlHelper::isValid($value, TRUE) ? $value : '';
  }

}
