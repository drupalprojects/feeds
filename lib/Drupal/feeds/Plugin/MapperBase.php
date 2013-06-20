<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\MapperBase.
 */

namespace Drupal\feeds\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\feeds\Plugin\Core\Entity\Importer;

/**
 * @todo Document this.
 */
class MapperBase extends PluginBase implements MapperInterface {

  public function sources(array &$sources, Importer $importer) {

  }

}
