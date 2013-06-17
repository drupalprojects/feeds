<?php

/**
 * @file
 * Contains \Drupal\feeds\Controller\FeedsController.
 */

namespace Drupal\feeds\Controller;

use Drupal\feeds\FeedInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\feeds\Plugin\Core\Entity\Importer;

/**
 * Returns responses for feeds module routes.
 */
class FeedsController implements ControllerInterface {

  /**
   * Stores the Entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a \Drupal\feeds\Controller\FeedsController object.
   *
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The Entity manager.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(EntityManager $entity_manager, ConfigFactory $config_factory, ModuleHandlerInterface $module_handler) {
    $this->entityManager = $entity_manager;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity'),
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * Presents the feeds feed creation form.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function feedAdd(Importer $importer = NULL) {
    dvm($importer);
    $feed = $this->entityManager
      ->getStorageController('feeds_feed')
      ->create(array(
        'uid' => $GLOBALS['user']->uid,
        'name' => (isset($user->name) ? $user->name : ''),
        'importer' => $importer->id(),
    ));
    return entity_get_form($feed);
  }

  /**
   * Refreshes a feed, then redirects to the overview page.
   *
   * @param \Drupal\feeds\FeedInterface $feeds_feed
   *   An object describing the feed to be refreshed.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirection to the admin overview page.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If the query token is missing or invalid.
   */
  public function feedRefresh(FeedInterface $feeds_feed, Request $request) {
    // @todo CSRF tokens are validated in page callbacks rather than access
    //   callbacks, because access callbacks are also invoked during menu link
    //   generation. Add token support to routing: http://drupal.org/node/755584.
    $token = $request->query->get('token');
    if (!isset($token) || !drupal_valid_token($token, 'feeds/update/' . $feeds_feed->id())) {
      throw new AccessDeniedHttpException();
    }

    // @todo after https://drupal.org/node/1972246 find a new place for it.
    feeds_refresh($feeds_feed);
    return new RedirectResponse(url('admin/config/services/feeds', array('absolute' => TRUE)));
  }

  /**
   * Displays the feeds administration page.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function adminOverview() {
    $result = $this->database->query('SELECT f.fid, f.title, f.url, f.refresh, f.checked, f.link, f.description, f.hash, f.etag, f.modified, f.image, f.block, COUNT(i.iid) AS items FROM {feeds_feed} f LEFT JOIN {feeds_item} i ON f.fid = i.fid GROUP BY f.fid, f.title, f.url, f.refresh, f.checked, f.link, f.description, f.hash, f.etag, f.modified, f.image, f.block ORDER BY f.title');

    $header = array(t('Title'), t('Items'), t('Last update'), t('Next update'), t('Operations'));
    $rows = array();
    foreach ($result as $feed) {
      $row = array();
      $row[] = l($feed->title, "feeds/sources/$feed->fid");
      $row[] = format_plural($feed->items, '1 item', '@count items');
      $row[] = ($feed->checked ? t('@time ago', array('@time' => format_interval(REQUEST_TIME - $feed->checked))) : t('never'));
      $row[] = ($feed->checked && $feed->refresh ? t('%time left', array('%time' => format_interval($feed->checked + $feed->refresh - REQUEST_TIME))) : t('never'));
      $links = array();
      $links['edit'] = array(
        'title' => t('Edit'),
        'href' => "admin/config/services/feeds/edit/feed/$feed->fid",
      );
      $links['delete'] = array(
        'title' => t('Delete'),
        'href' => "admin/config/services/feeds/delete/feed/$feed->fid",
      );
      $links['remove'] = array(
        'title' => t('Remove items'),
        'href' => "admin/config/services/feeds/remove/$feed->fid",
      );
      $links['update'] = array(
        'title' => t('Update items'),
        'href' => "admin/config/services/feeds/update/$feed->fid",
        'query' => array('token' => drupal_get_token("feeds/update/$feed->fid")),
      );
      $row[] = array(
        'data' => array(
          '#type' => 'operations',
          '#links' => $links,
        ),
      );
      $rows[] = $row;
    }
    $build['feeds'] = array(
      '#prefix' => '<h3>' . t('Feed overview') . '</h3>',
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' =>  t('No feeds available. <a href="@link">Add feed</a>.', array('@link' => url('admin/config/services/feeds/add/feed'))),
    );

    $result = $this->database->query('SELECT c.cid, c.title, COUNT(ci.iid) as items FROM {feeds_category} c LEFT JOIN {feeds_category_item} ci ON c.cid = ci.cid GROUP BY c.cid, c.title ORDER BY title');

    $header = array(t('Title'), t('Items'), t('Operations'));
    $rows = array();
    foreach ($result as $category) {
      $row = array();
      $row[] = l($category->title, "feeds/categories/$category->cid");
      $row[] = format_plural($category->items, '1 item', '@count items');
      $links = array();
      $links['edit'] = array(
        'title' => t('Edit'),
        'href' => "admin/config/services/feeds/edit/category/$category->cid",
      );
      $row[] = array(
        'data' => array(
          '#type' => 'operations',
          '#links' => $links,
        ),
      );
      $rows[] = $row;
    }
    $build['categories'] = array(
      '#prefix' => '<h3>' . t('Category overview') . '</h3>',
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' =>  t('No categories available. <a href="@link">Add category</a>.', array('@link' => url('admin/config/services/feeds/add/category'))),
    );

    return $build;
  }

  /**
   * Displays the most recent items gathered from any feed.
   *
   * @return string
   *   The rendered list of items for the feed.
   */
  public function pageLast() {
    drupal_add_feed('feeds/rss', $this->configFactory->get('system.site')->get('name') . ' ' . t('feeds'));

    // @todo Refactor this function once after all controller conversions are
    // done.
    $this->moduleHandler->loadInclude('feeds', 'inc', 'feeds.pages');
    $items = feeds_load_feed_items('sum');

    // @todo Refactor this function once after all controller conversions are
    // done.
    return _feeds_page_list($items, arg(1));
  }

  /**
   * Displays all the feeds used by the Feeds module.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function sources() {

    $feeds = $this->entityManager->getStorageController('feeds_feed')->load();

    $build = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('feeds-wrapper')),
      '#sorted' => TRUE,
    );

    // @todo remove this once feeds_load_feed_items() is refactored after
    // http://drupal.org/node/15266 is in.
    $this->moduleHandler->loadInclude('feeds', 'inc', 'feeds.pages');

    foreach ($feeds as $feed) {
      // Most recent items:
      $summary_items = array();
      $feeds_summary_items = $this->configFactory
        ->get('feeds.settings')
        ->get('source.list_max');
      if ($feeds_summary_items) {
        if ($items = feeds_load_feed_items('source', $feed, $feeds_summary_items)) {
          $summary_items = $this->entityManager
            ->getRenderController('feeds_item')
            ->viewMultiple($items, 'summary');
        }
      }
      $feed->url = url('feeds/sources/' . $feed->id());
      $build[$feed->id()] = array(
        '#theme' => 'feeds_summary_items',
        '#summary_items' => $summary_items,
        '#source' => $feed,
      );
    }
    $build['feed_icon'] = array(
      '#theme' => 'feed_icon',
      '#url' => 'feeds/opml',
      '#title' => t('OPML feed'),
    );
    return $build;
  }

}
