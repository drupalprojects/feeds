<?php

/**
 * @file
 * Contains \Drupal\feeds\Form\FeedClearForm.
 */

namespace Drupal\feeds\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for deleting the items from a feed.
 */
class FeedClearForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Delete all items from feed %feed?', array('%feed' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'feeds_feed',
      'route_parameters' => array('feeds_feed' => $this->entity->id()),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete items');
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->startClear();
    $args = array('@importer' => $this->entity->getImporter()->label(), '%title' => $this->entity->label());

    $form_state['redirect'] = $this->url('feeds_feed', array('feeds_feed' => $this->entity->id()));
  }

}
