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
 *   id = "feeds_feed_import_action",
 *   route_name = "feeds_feed.import",
 *   title = @Translation("Import"),
 *   appears_on = {"feeds_feed_view"}
 * )
 */
class FeedImportLocalAction extends LocalActionBase {

}
