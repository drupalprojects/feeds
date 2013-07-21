<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\Menu\LocalAction\FeedUnlockLocalAction.
 */

namespace Drupal\feeds\Plugin\Menu\LocalAction;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Menu\LocalActionBase;
use Drupal\Core\Annotation\Menu\LocalAction;

/**
 * @LocalAction(
 *   id = "feeds_feed_unlock_action",
 *   route_name = "feeds_feed.unlock",
 *   title = @Translation("Delete items"),
 *   appears_on = {"feeds_feed.view"}
 * )
 */
class FeedUnlockLocalAction extends LocalActionBase {

}
