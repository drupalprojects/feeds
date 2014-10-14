<?php

/**
 * @file
 * Contains \Drupal\feeds\Controller\ItemListController.
 */

namespace Drupal\feeds\Controller;

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\feeds\FeedInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Lists the feed items belonging to a feed.
 */
class ItemListController extends ControllerBase {

  /**
   * Lists the feed items belonging to a feed.
   */
  public function listItems(FeedInterface $feeds_feed, Request $request) {
    $processor = $feeds_feed->getImporter()->getProcessor();

    $limit = 50;
    $page = (int) $request->query->get('page');

    $total = \Drupal::entityQuery($processor->entityType())
    ->condition('feeds_item.target_id', $feeds_feed->id())
    ->count()
    ->execute();

    pager_default_initialize($total, $limit);

    $entity_ids = \Drupal::entityQuery($processor->entityType())
    ->condition('feeds_item.target_id', $feeds_feed->id())
    ->range($page * $limit, $limit)
    ->sort('feeds_item.imported', 'DESC')
    ->execute();

    $header = [
      'title' => $this->t('Label'),
      'imported' => $this->t('Imported'),
      'guid' => [
        'data' => $this->t('GUID'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'url' => [
        'data' => $this->t('URL'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];

    $build = [];
    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => array(),
      '#empty' => $this->t('There is no items yet.'),
    ];

    $storage = $this->entityManager()->getStorage($processor->entityType());
    foreach ($storage->loadMultiple($entity_ids) as $entity) {
      $ago = \Drupal::service('date.formatter')->formatInterval(REQUEST_TIME - $entity->get('feeds_item')->imported);

      $build['table']['#rows'][] = [
        [
          'data' => $entity->link(Unicode::truncate($entity->label(), 50, TRUE, TRUE)),
          'title' => String::checkPlain($entity->label()),
        ],
        $this->t('@time ago', ['@time' => $ago]),
        [
          'data' => Unicode::truncate(String::checkPlain($entity->get('feeds_item')->guid), 30, FALSE, TRUE),
          'title' => String::checkPlain($entity->get('feeds_item')->guid),
        ],
        [
          'data' => Unicode::truncate(String::checkPlain($entity->get('feeds_item')->url), 30, FALSE, TRUE),
          'title' => String::checkPlain($entity->get('feeds_item')->url),
        ],
      ];
    }

    $build['pager'] = ['#theme' => 'pager'];
    $build['#title'] = $this->t('%title items', ['%title' => $feeds_feed->label()]);

    return $build;
  }

}
