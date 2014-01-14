<?php

/**
 * @file
 * Contains \Drupal\feeds\Form\FeedDeleteForm.
 */

namespace Drupal\feeds\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;

/**
 * Provides a form for deleting a Feed.
 */
class FeedDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the feed %feed?', array('%feed' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   *
   * @todo Set the correct route once views can override paths.
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'feeds_feed.add_page',
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
    $args = array('@importer' => $this->entity->getImporter()->label(), '%title' => $this->entity->label());

    watchdog('feeds', '@importer: deleted %title.', $args);
    drupal_set_message($this->t('%title has been deleted.', $args));

    $form_state['redirect'] = 'admin/content/feed';
  }

}
