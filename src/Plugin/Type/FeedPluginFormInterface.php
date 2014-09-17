<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\Type\FeedPluginFormInterface.
 */

namespace Drupal\feeds\Plugin\Type;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\FeedInterface;

/**
 * Plugins implement this interface if they provide forms on a feed edit page.
 */
interface FeedPluginFormInterface {

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed currently being edited.
   *
   * @return array
   *   The form structure.
   */
  public function buildFeedForm(array $form, FormStateInterface $form_state, FeedInterface $feed);

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed currently being edited.
   *
   * @return array
   *   The form structure.
   */
  public function validateFeedForm(array &$form, FormStateInterface $form_state, FeedInterface $feed);

  /**
   * Form submit handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed currently being edited.
   *
   * @return array
   *   The form structure.
   */
  public function submitFeedForm(array &$form, FormStateInterface $form_state, FeedInterface $feed);

}
