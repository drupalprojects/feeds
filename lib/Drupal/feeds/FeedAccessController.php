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
 * @see \Drupal\feeds\Plugin\Core\Entity\Feed
 */
class FeedAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $feed, $operation, $langcode, AccountInterface $account) {
    if (!in_array($operation, array('view', 'create', 'update','delete', 'import', 'clear', 'unlock'))) {
      // If $operation is not one of the supported actions, we return access denied.
      return FALSE;
    }

    if ($operation === 'unlock') {
      if ($feed->progressImporting() == FEEDS_BATCH_COMPLETE && $feed->progressClearing() == FEEDS_BATCH_COMPLETE) {
        return FALSE;
      }
    }

    if (user_access('administer feeds', $account) || user_access("$operation {$feed->bundle()} feeds", $account)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    if (user_access('administer feeds', $account) || user_access("create $entity_bundle feeds", $account)) {
      return TRUE;
    }

    return FALSE;
  }

}
