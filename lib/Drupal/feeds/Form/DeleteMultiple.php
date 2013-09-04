<?php

/**
 * @file
 * Contains \Drupal\feeds\Form\DeleteMultiple.
 */

namespace Drupal\feeds\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Component\Utility\String;
use Drupal\user\TempStoreFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a feed deletion confirmation form.
 */
class DeleteMultiple extends ConfirmFormBase implements ContainerInjectionInterface {

  /**
   * The array of feeds to delete.
   *
   * @var array
   */
  protected $feeds = array();

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\TempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The node storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $manager;

  /**
   * Constructs a DeleteMultiple form object.
   *
   * @param \Drupal\user\TempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityManager $manager
   *   The entity manager.
   */
  public function __construct(TempStoreFactory $temp_store_factory, EntityManager $manager) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->storageController = $manager->getStorageController('feeds_feed');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.tempstore'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'feeds_feed_multiple_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return format_plural(count($this->feeds), 'Are you sure you want to delete this item?', 'Are you sure you want to delete these items?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelPath() {
    return 'admin/content/feed';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $this->feeds = $this->tempStoreFactory->get('feeds_feed_multiple_delete_confirm')->get($GLOBALS['user']->id());
    if (empty($this->feeds)) {
      return new RedirectResponse(url($this->getCancelPath(), array('absolute' => TRUE)));
    }

    $form['feeds'] = array(
      '#theme' => 'item_list',
      '#items' => array_map(function ($node) {
        return String::checkPlain($node->label());
      }, $this->feeds),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    if ($form_state['values']['confirm'] && !empty($this->feeds)) {
      $this->storageController->delete($this->feeds);
      $this->tempStoreFactory->get('feeds_multiple_delete_confirm')->delete($GLOBALS['user']->id());
      $count = count($this->feeds);
      watchdog('content', 'Deleted @count feeds.', array('@count' => $count));
      drupal_set_message(format_plural($count, 'Deleted 1 feed.', 'Deleted @count posts.', array('@count' => $count)));
    }
    $form_state['redirect'] = 'admin/content/feed';
  }

}
