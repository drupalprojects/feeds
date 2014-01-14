<?php

/**
 * @file
 * Contains \Drupal\feeds\ImporterStorageController.
 */

namespace Drupal\feeds;

use Drupal\Core\Config\Entity\ConfigStorageController;

/**
 * Defines the storage controller class for Importer entities.
 */
class ImporterStorageController extends ConfigStorageController {

  /**
   * Loads all enabled importers.
   *
   * @return \Drupal\feeds\ImporterInterface[]
   *   The list of enabled importers, keyed by id.
   */
  public function loadEnabled() {
    // Status has to be a string.
    return $this->loadByProperties(array('status' => TRUE));
  }

}
