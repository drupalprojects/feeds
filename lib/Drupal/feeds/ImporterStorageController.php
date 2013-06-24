<?php

/**
 * @file
 * Contains \Drupal\feeds\ImporterStorageController.
 */

namespace Drupal\feeds;

use Drupal\Core\Config\Entity\ConfigStorageController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the storage controller class for Importer entities.
 */
class ImporterStorageController extends ConfigStorageController {

  /**
   * {@inheritdoc}
   */
  public function save(EntityInterface $importer) {
    $plugins = array();

    // We do this little dance with the plugins so that they aren't saved.
    // @todo convert $importer->$type to $importer->$type().
    foreach ($importer->getPluginTypes() as $type) {
      $importer->config[$type]['config'] = $importer->$type->getConfig();
      $plugins[$type] = $importer->$type;
      unset($importer->$type);
    }

    parent::save();

    foreach ($plugins as $type => $plugin) {
      $importer->$type = $plugin;
    }
  }

}
