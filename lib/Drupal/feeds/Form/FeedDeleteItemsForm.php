<?php
/**
 * @file
 * Contains \Drupal\feeds\Form\FeedDeleteItemsForm.
 */

namespace Drupal\feeds\Form;

use Drupal\Core\Entity\EntityNGConfirmFormBase;

/**
 * Provides a form for deleting the items from a feed.
 */
class FeedDeleteItemsForm extends EntityNGConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Delete all items from feed %feed?', array('%feed' => $this->entity->label()));
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
    return t('Delete items');
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    $this->entity->startClear();
    $form_state['redirect'] = 'feed/' . $this->entity->id();
  }

}
