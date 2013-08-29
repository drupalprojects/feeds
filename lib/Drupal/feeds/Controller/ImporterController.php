<?php

/**
 * @file
 * Contains \Drupal\feeds\Controller\ImporterController.
 */

namespace Drupal\feeds\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for importer routes.
 */
class ImporterController extends ControllerBase {

  /**
   * Presents the importer creation form.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function createForm() {
    $importer = $this->entityManager()
      ->getStorageController('feeds_importer')
      ->create(array());

    return $this->entityManager()->getForm($importer, 'edit');
  }

}
