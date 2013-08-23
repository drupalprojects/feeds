<?php

/**
 * @file
 * Contains \Drupal\feeds\Form\FeedClearForm.
 */

namespace Drupal\feeds\Form;

use Drupal\Core\Entity\EntityNGConfirmFormBase;

/**
 * Provides a form for deleting the items from a feed.
 */
class FeedClearForm extends EntityNGConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Delete all items from feed %feed?', array('%feed' => $this->entity->label()));
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
    return $this->t('Delete items');
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    $this->entity->startClear();
    $args = array('@importer' => $this->entity->getImporter()->label(), '%title' => $this->entity->label());

    watchdog('feeds', '@importer: cleared %title.', $args);
    drupal_set_message($this->t('The items from %title have been deleted.', $args));

    $form_state['redirect'] = $this->getCancelPath();
  }

}
