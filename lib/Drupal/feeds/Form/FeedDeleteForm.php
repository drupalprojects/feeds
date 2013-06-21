<?php
/**
 * @file
 * Contains \Drupal\feeds\Form\FeedDeleteForm.
 */

namespace Drupal\feeds\Form;

use Drupal\Core\Entity\EntityNGConfirmFormBase;

/**
 * Provides a form for deleting a feed.
 */
class FeedDeleteForm extends EntityNGConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the feed %feed?', array('%feed' => $this->entity->label()));
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
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $this->entity->delete();
    watchdog('feeds', '@importer: deleted %title.', array('@importer' => $this->entity->getImporter()->label(), '%title' => $this->entity->label()));
    drupal_set_message(t('@importer %title has been deleted.', array('@importer' => $this->entity->getImporter()->label(), '%title' => $this->entity->label())));
    $form_state['redirect'] = 'admin/content/feed';
  }

}
