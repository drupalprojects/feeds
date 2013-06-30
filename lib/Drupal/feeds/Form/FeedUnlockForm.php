<?php
/**
 * @file
 * Contains \Drupal\feeds\Form\FeedUnlockForm.
 */

namespace Drupal\feeds\Form;

use Drupal\Core\Entity\EntityNGConfirmFormBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a form for unlocking a feed.
 */
class FeedUnlockForm extends EntityNGConfirmFormBase {

  /**
   * @var bool $isLocked
   *   Whether the feed is locked. Defaults to TRUE.
   */
  protected $isLocked = TRUE;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to unlock the feed %feed?', array('%feed' => $this->entity->label()));
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
    return t('Unlock');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $this->entity->unlock();
    watchdog('feeds', '@importer: unlocked %title.', array('@importer' => $this->entity->getImporter()->label(), '%title' => $this->entity->label()));
    drupal_set_message(t('@importer %title has been unlocked.', array('@importer' => $this->entity->getImporter()->label(), '%title' => $this->entity->label())));
    $form_state['redirect'] = 'feed/' . $this->entity->id();
  }

}
