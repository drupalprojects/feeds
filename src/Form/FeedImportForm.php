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
  public function getCancelUrl() {
    return $this->entity->urlInfo();
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // The import process will create its own messages.
    $this->entity->startImport();
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
