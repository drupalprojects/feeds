<?php

/**
 * @file
 * Contains \Drupal\feeds\Form\FeedImportForm.
 */

namespace Drupal\feeds\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for importing a feed.
 */
class FeedImportForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to import the feed %feed?', array('%feed' => $this->entity->label()));
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
    return $this->t('Import');
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // The import process will create its own messages.
    $this->entity->startImport();
    $form_state['redirect'] = $this->url('feeds_feed', array('feeds_feed' => $this->entity->id()));
  }

}
