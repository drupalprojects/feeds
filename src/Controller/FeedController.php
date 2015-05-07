<?php

/**
 * @file
 * Contains \Drupal\feeds\Controller\FeedController.
 */

namespace Drupal\feeds\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedTypeInterface;

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
  public function addPage() {
    // Show add form if there is only one feed type.
    $feed_types = $this->entityManager()
      ->getStorage('feeds_feed_type')
      ->loadByProperties(['status' => TRUE]);
    // There is an access checker on this route that determines if the user can
    // create at least one feed type. If there is only one enabled type, this
    // must be it.
    if ($feed_types && count($feed_types) == 1) {
      $feed_type = reset($feed_types);
      return $this->createForm($feed_type);
    }

    // @todo Don't show link for non-admins.
    $url = $this->url('feeds.overview_types');
    $empty = $this->t('There are no feed types, go to <a href="@types">Feed types</a> to create one or enable an existing one.', ['@types' => $url]);

    $build = [
      '#theme' => 'table',
      '#header' => [$this->t('Import'), $this->t('Description')],
      '#rows' => [],
      '#empty' => $empty,
    ];

    foreach ($feed_types as $feed_type) {
      if (!($feed_type->access('create'))) {
        continue;
      }
      $build['#rows'][] = [
        $this->l($feed_type->label(), new Url('feeds.add', ['feeds_feed_type' => $feed_type->id()])),
        SafeMarkup::checkPlain($feed_type->getDescription()),
      ];
    }

    return $build;
  }

  /**
   * Presents the feed creation form.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function createForm(FeedTypeInterface $feeds_feed_type) {
    $feed = $this->entityManager()->getStorage('feeds_feed')->create([
      'uid' => $this->currentUser()->id(),
      'type' => $feeds_feed_type->id(),
      // 'status' => 1,
      'created' => REQUEST_TIME,
    ]);
    return $this->entityFormBuilder()->getForm($feed);
  }

}
