<?php

/**
 * @file
 * Contains \Drupal\feeds\Form\FeedDeleteForm.
 */

namespace Drupal\feeds\Form;

use Drupal\Core\Entity\EntityNGConfirmFormBase;

/**
 * Provides a form for deleting a Feed.
 */
class FeedDeleteForm extends EntityNGConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the feed %feed?', array('%feed' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelPath() {
    return 'feed/' . $this->entity->id();
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
    $args = array('@importer' => $this->entity->getImporter()->label(), '%title' => $this->entity->label());

    watchdog('feeds', '@importer: deleted %title.', $args);
    drupal_set_message($this->t('%title has been deleted.', $args));

    $form_state['redirect'] = 'admin/content/feed';
  }

}
