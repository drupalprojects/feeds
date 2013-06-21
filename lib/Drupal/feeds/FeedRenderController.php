<?php

/**
 * @file
 * Definition of Drupal\feeds\FeedRenderController.
 */

namespace Drupal\feeds;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRenderController;
use Drupal\entity\Plugin\Core\Entity\EntityDisplay;
use Drupal\feeds\Plugin\Core\Entity\Feed;

/**
 * Render controller for feedss.
 */
class FeedRenderController extends EntityRenderController {

  /**
   * Overrides Drupal\Core\Entity\EntityRenderController::buildContent().
   */
  public function buildContent(array $feeds, array $displays, $view_mode, $langcode = NULL) {
    $return = array();
    if (empty($feeds)) {
      return $return;
    }

    parent::buildContent($feeds, $displays, $view_mode, $langcode);

    foreach ($feeds as $key => $feed) {
      $importer = $feed->bundle();
      $display = $displays[$importer];
      $feed->content['feed_status'] = $this->getStatus($feed);
    }
  }

  /**
   * @todo Convert this to a twig template.
   */
  protected function getStatus(Feed $feed) {
    $progress_importing = $feed->progressImporting();
    $v = array();
    if ($progress_importing != FEEDS_BATCH_COMPLETE) {
      $v['#progress_importing'] = $progress_importing;
    }
    $progress_clearing = $feed->progressClearing();
    if ($progress_clearing != FEEDS_BATCH_COMPLETE) {
      $v['#progress_clearing'] = $progress_clearing;
    }
    $v['#imported'] = $feed->imported->value;
    $v['#count'] = $feed->itemCount();
    $v['#theme'] = 'feeds_feed_status';
    if (!empty($v)) {
      return $v;
    }
  }

}
