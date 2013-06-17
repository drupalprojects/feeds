<?php

/**
 * @file
 * Definition of Drupal\feeds\FeedFormController.
 */

namespace Drupal\feeds;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityFormControllerNG;

/**
 * Form controller for the feed edit forms.
 */
class FeedFormController extends EntityFormControllerNG {

  /**
   * Overrides Drupal\Core\Entity\EntityFormControllerNG::form().
   */
  public function form(array $form, array &$form_state) {
    $feed = $this->entity;

    $importer = $feed->getImporter();

    if ($this->operation == 'edit') {
      drupal_set_title(t('<em>Edit @importer</em> @title', array('@importer' => $importer->label(), '@title' => $feed->title->value), PASS_THROUGH));
    }

    $user_config = config('user.settings');

    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#default_value' => $feed->title->value,
      '#required' => TRUE,
    );

    $feed_config = $feed->getConfig();

    foreach ($feed->getImporter()->getPluginTypes() as $type) {
      $plugin = $feed->getImporter()->$type;
      $plugin_id = $plugin->getPluginId();

      $config = isset($feed_config[$plugin_id]) ? $feed_config[$plugin_id] : array();

      $form[$type] = $plugin->sourceForm($config);
      $form[$type]['#tree'] = TRUE;
    }

    $form['advanced'] = array(
      '#type' => 'vertical_tabs',
      '#weight' => 99,
    );

    // Feed author information for administrators.
    $form['author'] = array(
      '#type' => 'details',
      '#access' => user_access('administer feeds'),
      '#title' => t('Authoring information'),
      '#collapsed' => TRUE,
      '#group' => 'advanced',
      '#weight' => 90,
    );

    $form['author']['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Authored by'),
      '#maxlength' => 60,
      '#autocomplete_path' => 'user/autocomplete',
      '#default_value' => $feed->getUser()->name,
      '#description' => t('Leave blank for %anonymous.', array('%anonymous' => $user_config->get('anonymous'))),
    );
    $form['author']['date'] = array(
      '#type' => 'textfield',
      '#title' => t('Authored on'),
      '#maxlength' => 25,
      '#description' => t('Format: %time. The date format is YYYY-MM-DD and %timezone is the time zone offset from UTC. Leave blank to use the time of form submission.', array(
        '%time' => format_date($feed->created->value, 'custom', 'Y-m-d H:i:s O'),
        '%timezone' => format_date($feed->created->value, 'custom', 'O'),
      )),
    );

    // Feed options for administrators.
    $form['options'] = array(
      '#type' => 'details',
      '#access' => user_access('administer feeds'),
      '#title' => t('Import options'),
      '#collapsed' => TRUE,
      '#group' => 'advanced',
    );

    $form['options']['status'] = array(
      '#type' => 'checkbox',
      '#title' => t('Active'),
      '#default_value' => $feed->status->value,
    );

    return parent::form($form, $form_state);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormControllerNG::actions().
   */
  protected function actions(array $form, array &$form_state) {
    $element = parent::actions($form, $form_state);
    $feed = $this->entity;

    return $element;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormControllerNG::validate().
   */
  public function validate(array $form, array &$form_state) {
    $feed = $this->buildEntity($form, $form_state);

    $importer = $feed->getImporter();
    foreach ($importer->getPluginTypes() as $type) {
      $importer->$type->sourceFormValidate($form_state['values'][$type]);
    }

    // Validate the "authored by" field.
    // if (!empty($feed->name) && !($account = user_load_by_name($feed->name))) {
    //   // The use of empty() is mandatory in the context of usernames
    //   // as the empty string denotes the anonymous user. In case we
    //   // are dealing with an anonymous user we set the user ID to 0.
    //   form_set_error('name', t('The username %name does not exist.', array('%name' => $feed->name)));
    // }

    // Validate the "authored on" field.
    // The date element contains the date object.
    // $date = $feed->date instanceof DrupalDateTime ? $feed->date : new DrupalDateTime($feed->date);
    // if ($date->hasErrors()) {
    //   form_set_error('date', t('You have to specify a valid date.'));
    // }

    parent::validate($form, $form_state);
  }

  /**
   * Updates the feed object by processing the submitted values.
   *
   * This function can be called by a "Next" button of a wizard to update the
   * form state's entity with the current step's values before proceeding to the
   * next step.
   *
   * Overrides Drupal\Core\Entity\EntityFormControllerNG::submit().
   */
  public function submit(array $form, array &$form_state) {
    // Build the feed object from the submitted values.
    $feed = parent::submit($form, $form_state);

    foreach ($feed->getImporter()->getPluginTypes() as $type) {
      $plugin = $feed->getImporter()->$type;
      $plugin->sourceFormSubmit($form_state['values'][$type]);
      $feed->setConfigFor($plugin, $form_state['values'][$type]);
    }

    return $feed;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormControllerNG::save().
   */
  public function save(array $form, array &$form_state) {
    $feed = $this->entity;
    $insert = !(bool) $feed->id();
    $importer = $feed->getImporter();
    $feed->save();

    $feed_link = l(t('view'), 'feed/' . $feed->id());
    $watchdog_args = array('@importer' => $feed->type, '%title' => $feed->title->value);
    $t_args = array('@importer' => $importer->label(), '%title' => $feed->title->value);

    if ($insert) {
      watchdog('feeds', '@importer: added %title.', $watchdog_args, WATCHDOG_NOTICE, $feed_link);
      drupal_set_message(t('@importer %title has been created.', $t_args));
    }
    else {
      watchdog('feeds', '@importer: updated %title.', $watchdog_args, WATCHDOG_NOTICE, $feed_link);
      drupal_set_message(t('@importer %title has been updated.', $t_args));
    }

    if ($feed->id()) {
      $form_state['values']['fid'] = $feed->id();
      $form_state['fid'] = $feed->id();
      $form_state['redirect'] = 'feed/' . $feed->id();
    }
    else {
      // In the unlikely case something went wrong on save, the feed will be
      // rebuilt and feed form redisplayed the same way as in preview.
      drupal_set_message(t('The feed could not be saved.'), 'error');
      $form_state['rebuild'] = TRUE;
    }

    // Clear the page and block caches.
    cache_invalidate_tags(array('feeds' => TRUE));

    // Schedule jobs for this feed.
    $feed->schedule();

    if ($insert && $feed->getImporter()->config['import_on_create']) {
      $feed->startImport();
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormControllerNG::delete().
   */
  public function delete(array $form, array &$form_state) {
    $destination = array();
    $query = \Drupal::request()->query;
    if ($query->has('destination')) {
      $destination = drupal_get_destination();
      $query->remove('destination');
    }
    $feed = $this->entity;
    $form_state['redirect'] = array('feed/' . $feed->id() . '/delete', array('query' => $destination));
  }

}
