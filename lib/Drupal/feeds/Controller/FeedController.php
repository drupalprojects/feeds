<?php

/**
 * @file
 * Contains \Drupal\feeds\Controller\FeedController.
 */

namespace Drupal\feeds\Controller;

use Drupal\Component\Utility\String;
use Drupal\Core\Controller\ControllerBase;
use Drupal\feeds\FeedInterface;

/**
 * Returns responses for feed routes.
 */
class FeedController extends ControllerBase {

  /**
   * Presents the feeds feed creation form.
   *
   * @return array
   *   A form array as expected by drupal_render().
   *
   * @todo When node page gets converted, look at the implementation.
   */
  public function createList() {
    // Show add form if there is only one importer.
    $importers = $this->entityManager()
      ->getStorageController('feeds_importer')
      ->loadEnabled();
    // There is an access checker on this route that determines if the user can
    // create at least one importer. If there is only one enabled importer, this
    // must be it.
    if ($importers && count($importers) == 1) {
      $importer = reset($importers);
      return $this->createForm($importer);
    }

    // @todo Don't show link for non-admins.
    $url = $this->urlGenerator()->generateFromRoute('feeds_importer.list');
    $empty = $this->t('There are no importers, go to <a href="@importers">Feed importers</a> to create one or enable an existing one.', array('@importers' => $url));

    $build = array(
      '#theme' => 'table',
      '#header' => array($this->t('Import'), $this->t('Description')),
      '#rows' => array(),
      '#empty' => $empty,
    );

    foreach ($importers as $importer) {
      if (!($importer->access('create'))) {
        continue;
      }
      $build['#rows'][] = array(
        $this->l($importer->label(), 'feeds_feed.create', array('feeds_importer' => $importer->id())),
        String::checkPlain($importer->description),
      );
    }

    return $build;
  }

  /**
   * Presents the feed creation form.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function createForm(Importer $feeds_importer) {

    $feed = $this->entityManager()->getStorageController('feeds_feed')->create(array(
      'uid' => $this->currentUser()->id(),
      'importer' => $feeds_importer->id(),
      'status' => 1,
      'created' => REQUEST_TIME,
    ));

    return $this->entityManager()->getForm($feed, 'create');
  }

}
