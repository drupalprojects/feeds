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

    // This will grab configuration from plugins and handlers.
    // foreach ($importer->getPluginTypes() as $type) {
    //   $importer->{$type}['config'] = $importer->getPlugin($type)->getConfiguration();
    // }

    parent::save($importer);
  }

  public function loadEnabled() {
    return $this->loadByProperties(array('status' => 1));
  }

}
