<?php

/**
 * @file
 * Contains \Drupal\feeds\FeedAccessController.
 */

namespace Drupal\feeds;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the feeds_feed entity.
 *
 * @see \Drupal\feeds\Entity\Feed
 */
class FeedAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $feed, $operation, $langcode, AccountInterface $account) {
    if (!in_array($operation, array('view', 'create', 'update','delete', 'import', 'clear', 'unlock'))) {
      // If $operation is not one of the supported actions, we return access
      // denied.
      return FALSE;
    }

    if ($operation === 'unlock') {
      // If there is no need to unlock the feed, then the user does not have
      // access.
      if ($feed->progressImporting() == FEEDS_BATCH_COMPLETE && $feed->progressClearing() == FEEDS_BATCH_COMPLETE) {
        return FALSE;
      }
    }

    return $account->hasPermission('administer feeds') || $account->hasPermission("$operation {$feed->bundle()} feeds");
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return $account->hasPermission('administer feeds') || $account->hasPermission("create $entity_bundle feeds");
  }

}
