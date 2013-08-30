<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Target\Link.
 */

namespace Drupal\feeds\Plugin\feeds\Target;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\feeds\FeedsElement;
use Drupal\feeds\Plugin\FieldTargetBase;
use Drupal\field\Entity\FieldInstance;

/**
 * Defines a link field mapper.
 *
 * @Plugin(
 *   id = "link",
 *   title = @Translation("Link"),
 *   field_types = {"link"}
 * )
 */
class Link extends FieldTargetBase {

  protected function prepareTarget(array &$target) {
    unset($target['properties']['attributes']);
  }

}
