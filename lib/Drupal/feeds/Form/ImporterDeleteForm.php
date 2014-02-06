<?php

/**
 * @file
 * Contains \Drupal\feeds\Form\ImporterDeleteForm.
 */

namespace Drupal\feeds\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;

/**
 * Provides a form for deleting an Importer.
 */
class ImporterDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the importer %importer?', array('%importer' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'feeds.importer_list',
    );
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
  public function submit(array $form, array &$form_state) {
    $this->entity->delete();
    $args = array('%importer' => $this->entity->label());

    watchdog('feeds', 'Deleted importer: %importer.', $args);
    drupal_set_message($this->t('%importer has been deleted.', $args));

    $form_state['redirect'] = $this->url('feeds.importer_list');
  }

}
