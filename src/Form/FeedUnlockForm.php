<?php

/**
 * @file
 * Contains \Drupal\feeds\Form\FeedUnlockForm.
 */

namespace Drupal\feeds\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a form for unlocking a feed.
 */
class FeedUnlockForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to unlock the feed %feed?', array('%feed' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'feeds_feed',
      'route_parameters' => array('feeds_feed' => $this->entity->id()),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Unlock');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, FormStateInterface $form_state) {
    $this->entity->unlock();
    $args = array('@importer' => $this->entity->getImporter()->label(), '%title' => $this->entity->label());

    watchdog('feeds', '@importer: unlocked %title.', $args);
    drupal_set_message($this->t('%title has been unlocked.', $args));

    // $form_state['redirect'] = $this->url('feeds_feed', array('feeds_feed' => $this->entity->id()));
  }

}
