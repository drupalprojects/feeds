<?php

/**
 * @file
 * Contains \Drupal\feeds\Form\ImporterDeleteForm.
 */

namespace Drupal\feeds\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting an Importer.
 */
class ImporterDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the importer %importer?', ['%importer' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('feeds.importer_list');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    $args = ['%importer' => $this->entity->label()];
    $this->logger('feeds')->notice('Deleted importer: %importer.', $args);
    drupal_set_message($this->t('%importer has been deleted.', $args));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
