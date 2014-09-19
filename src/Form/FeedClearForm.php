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
  public function getCancelUrl() {
    return $this->entity->urlInfo();
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->startClear();
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
