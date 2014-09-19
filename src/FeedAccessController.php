<?php

/**
 * @file
 * Contains \Drupal\feeds\FeedAccessController.
 */

namespace Drupal\feeds;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\feeds\StateInterface;

/**
 * Defines an access controller for the feeds_feed entity.
 *
 * @see \Drupal\feeds\Entity\Feed
 */
class FeedAccessController extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $feed, $operation, $langcode, AccountInterface $account) {
    if (!in_array($operation, array('view', 'create', 'update', 'delete', 'import', 'clear', 'unlock'))) {
      // If $operation is not one of the supported actions, we return access
      // denied.
      AccessResult::forbidden();
    }

    if ($operation === 'unlock') {
      // If there is no need to unlock the feed, then the user does not have
      // access.
      if ($feed->progressImporting() == StateInterface::BATCH_COMPLETE && $feed->progressClearing() == StateInterface::BATCH_COMPLETE) {
        return AccessResult::forbidden();
      }
    }

    $has_perm = $account->hasPermission('administer feeds') || $account->hasPermission("$operation {$feed->bundle()} feeds");
    return AccessResult::allowedIf($has_perm);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $has_perm = $account->hasPermission('administer feeds') || $account->hasPermission("create $entity_bundle feeds");
    return AccessResult::allowedIf($has_perm);
  }

}
