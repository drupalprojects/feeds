<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\Menu\LocalAction\FeedClearLocalAction.
 */

namespace Drupal\feeds\Plugin\Menu\LocalAction;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Menu\LocalActionBase;
use Drupal\Core\Annotation\Menu\LocalAction;

/**
 * @LocalAction(
 *   id = "feeds_feed_clear_action",
 *   route_name = "feeds_feed.clear",
 *   title = @Translation("Delete items"),
 *   appears_on = {"feeds_feed.view"}
 * )
 */
class FeedClearLocalAction extends LocalActionBase {

}
