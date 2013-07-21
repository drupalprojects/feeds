<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\Menu\LocalAction\ImporterAddLocalAction.
 */

namespace Drupal\feeds\Plugin\Menu\LocalAction;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Menu\LocalActionBase;
use Drupal\Core\Annotation\Menu\LocalAction;

/**
 * @LocalAction(
 *   id = "feeds_importer_add_action",
 *   route_name = "feeds_importer.create",
 *   title = @Translation("Add importer"),
 *   appears_on = {"feeds_importer.list"}
 * )
 */
class ImporterAddLocalAction extends LocalActionBase {

}
