<?php

/**
 * @file
 * Definition of Drupal\feeds\FeedFormController.
 */

namespace Drupal\feeds;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityFormControllerNG;
use Drupal\feeds\Plugin\Type\FeedPluginFormInterface;

/**
 * Form controller for the feed edit forms.
 */
class FeedFormController extends EntityFormControllerNG {

  /**
   * Plugins that provide configuration forms.
   *
   * @var array
   */
  protected $configurablePlugins = array();

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $feed = $this->entity;

    $importer = $feed->getImporter();

    $args = array('@importer' => $importer->label(), '@title' => $feed->label());
    if ($this->operation == 'update') {
      drupal_set_title($this->t('<em>Edit @importer</em> @title', $args), PASS_THROUGH);
    }
    elseif ($this->operation == 'create') {
      drupal_set_title($this->t('<em>Add @importer</em>', $args), PASS_THROUGH);
    }

    $user_config = \Drupal::config('user.settings');

    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $feed->label(),
      '#required' => TRUE,
    );

    foreach ($importer->getPlugins() as $plugin) {
      if ($plugin instanceof FeedPluginFormInterface) {
        // Store the plugin for validate and submit.
        $this->configurablePlugins[] = $plugin;
        $form = $plugin->buildFeedForm($form, $form_state, $feed);
      }
    }

    $form['advanced'] = array(
      '#type' => 'vertical_tabs',
      '#weight' => 99,
    );

    // Feed author information for administrators.
    $form['author'] = array(
      '#type' => 'details',
      '#access' => $this->currentUser()->hasPermission('administer feeds'),
      '#title' => $this->t('Authoring information'),
      '#collapsed' => TRUE,
      '#group' => 'advanced',
      '#weight' => 90,
    );

    $form['author']['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Authored by'),
      '#maxlength' => 60,
      '#autocomplete_path' => 'user/autocomplete',
      '#default_value' => $feed->getAuthor()->getUsername(),
      '#description' => $this->t('Leave blank for %anonymous.', array('%anonymous' => $user_config->get('anonymous'))),
    );
    $form['author']['date'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Authored on'),
      '#maxlength' => 25,
      '#description' => $this->t('Format: %time. The date format is YYYY-MM-DD and %timezone is the time zone offset from UTC. Leave blank to use the time of form submission.', array(
        '%time' => format_date($feed->getCreatedTime(), 'custom', 'Y-m-d H:i:s O'),
        '%timezone' => format_date($feed->getCreatedTime(), 'custom', 'O'),
      )),
    );

    // Feed options for administrators.
    $form['options'] = array(
      '#type' => 'details',
      '#access' => $this->currentUser()->hasPermission('administer feeds'),
      '#title' => $this->t('Import options'),
      '#collapsed' => TRUE,
      '#group' => 'advanced',
    );

    $form['options']['status'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Active'),
      '#default_value' => $feed->isActive(),
    );

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @todo Don't call buildEntity() here.
   */
  public function validate(array $form, array &$form_state) {

    $feed = $this->buildEntity($form, $form_state);

    foreach ($this->configurablePlugins as $plugin) {
      $plugin->validateFeedForm($form, $form_state, $feed);
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
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    // Build the feed object from the submitted values.
    $feed = parent::submit($form, $form_state);

    foreach ($this->configurablePlugins as $plugin) {
      $plugin->submitFeedForm($form, $form_state, $feed);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    $feed = $this->entity;
    $insert = !(bool) $feed->id();
    $importer = $feed->getImporter();
    $feed->save();

    $feed_link = l($this->t('View'), 'feed/' . $feed->id());
    $args = array('@importer' => $importer->label(), '%title' => $feed->label());

    if ($insert) {
      watchdog('feeds', '@importer: added %title.', $args, WATCHDOG_NOTICE, $feed_link);
      drupal_set_message($this->t('%title has been created.', $args));
    }
    else {
      watchdog('feeds', '@importer: updated %title.', $args, WATCHDOG_NOTICE, $feed_link);
      drupal_set_message($this->t('%title has been updated.', $args));
    }

    if ($feed->id()) {
      $form_state['redirect'] = 'feed/' . $feed->id();

      // Clear feed cache.
      Cache::invalidateTags(array('feeds' => TRUE));

      // Schedule jobs for this feed.
      $feed->schedule();
    }
    else {
      // In the unlikely case something went wrong on save, the feed will be
      // rebuilt and feed form redisplayed the same way as in preview.
      drupal_set_message($this->t('The feed could not be saved.'), 'error');
      $form_state['rebuild'] = TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $form, array &$form_state) {
    $destination = array();
    $query = $this->getRequest()->query;

    if ($query->has('destination')) {
      $destination = drupal_get_destination();
      $query->remove('destination');
    }

    $feed = $this->entity;
    $form_state['redirect'] = array('feed/' . $feed->id() . '/delete', array('query' => $destination));
  }

}
