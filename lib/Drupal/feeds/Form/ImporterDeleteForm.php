<?php
/**
 * @file
 * Contains \Drupal\feeds\Form\ImporterDeleteForm.
 */

namespace Drupal\feeds\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;

/**
 * Provides a form for deleting a feed.
 */
class ImporterDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the importer %importer?', array('%importer' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelPath() {
    return 'admin/structure/feeds';
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
  public function submit(array $form, array &$form_state) {
    $this->entity->delete();
    watchdog('feeds', 'Deleted importer @importer.', array('@importer' => $this->entity->label()));
    drupal_set_message(t('%importer has been deleted.', array('%importer' => $this->entity->label())));
    $form_state['redirect'] = 'admin/structure/feeds';
  }

}
