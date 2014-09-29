<?php

/**
 * @file
 * Contains \Drupal\feeds\ImporterAccessController.
 */

namespace Drupal\feeds;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the feeds_importer entity.
 *
 * @see \Drupal\feeds\Entity\Importer
 *
 * @todo Provide more granular permissions.
 */
class ImporterAccessController extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    switch ($operation) {
      case 'delete':
        return AccessResult::allowedIf($account->hasPermission('administer feeds') && !$entity->isLocked());

      default:
        return AccessResult::allowedIfHasPermission($account, 'administer feeds');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'administer feeds');
  }

}
