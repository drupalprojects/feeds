<?php

/**
 * @file
 * Contains \Drupal\feeds\ImporterAccessController.
 */

namespace Drupal\feeds;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the importer entity.
 *
 * @see \Drupal\feeds\Plugin\Core\Entity\Importer.
 */
class ImporterAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    return user_access('administer feeds', $account);
  }

}
