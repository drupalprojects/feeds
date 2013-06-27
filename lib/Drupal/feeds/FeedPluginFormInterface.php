<?php

/**
 * @file
 * Contains \Drupal\feeds\FeedPluginFormInterface.
 */

namespace Drupal\feeds;

/**
 * Plugins implement this interface if they provide forms on a feed edit page.
 */
interface FeedPluginFormInterface {

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   * @param FeedInterface $feed
   *   The feed currently being edited.
   *
   * @return array
   *   The form structure.
   */
  public function feedForm(array $form, array &$form_state, FeedInterface $feed);

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   * @param FeedInterface $feed
   *   The feed currently being edited.
   *
   * @return array
   *   The form structure.
   */
  public function feedFormValidate(array $form, array &$form_state, FeedInterface $feed);

  /**
   * Form submit handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   * @param FeedInterface $feed
   *   The feed currently being edited.
   *
   * @return array
   *   The form structure.
   */
  public function feedFormSubmit(array $form, array &$form_state, FeedInterface $feed);

}
