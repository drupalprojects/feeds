<?php

/**
 * @file
 * Definition of Drupal\feeds\FeedFormController.
 */

namespace Drupal\feeds;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Plugin\Type\FeedPluginFormInterface;

/**
 * Form controller for the feed edit forms.
 */
class FeedFormController extends ContentEntityForm {

  /**
   * Plugins that provide configuration forms.
   *
   * @var array
   */
  protected $configurablePlugins = array();

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $feed = $this->entity;

    $importer = $feed->getImporter();

    $args = array('@importer' => $importer->label(), '@title' => $feed->label());
    if ($this->operation == 'update') {
      $form['#title'] = $this->t('<em>Edit @importer</em> @title', $args);
    }
    elseif ($this->operation == 'create') {
      $form['#title'] = $this->t('<em>Add @importer</em>', $args);
    }

    $form['advanced'] = array(
      '#type' => 'vertical_tabs',
      '#attributes' => array('class' => array('entity-meta')),
      '#weight' => 99,
    );
    $form = parent::form($form, $form_state);

    foreach ($importer->getPlugins() as $plugin) {
      if ($plugin instanceof FeedPluginFormInterface) {
        // Store the plugin for validate and submit.
        $this->configurablePlugins[] = $plugin;
        $form = $plugin->buildFeedForm($form, $form_state, $feed);
      }
    }

    $form['author'] = array(
      '#type' => 'details',
      '#title' => t('Authoring information'),
      '#group' => 'advanced',
      '#attributes' => array(
        'class' => array('feeds-feed-form-author'),
      ),
      '#weight' => 90,
      '#optional' => TRUE,
    );
    if (isset($form['uid'])) {
      $form['uid']['#group'] = 'author';
    }
    if (isset($form['created'])) {
      $form['created']['#group'] = 'author';
    }

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

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Don't call buildEntity() here.
   */
  public function validate(array $form, FormStateInterface $form_state) {

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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Build the feed object from the submitted values.
    parent::submitForm($form, $form_state);

    foreach ($this->configurablePlugins as $plugin) {
      $plugin->submitFeedForm($form, $form_state, $this->entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
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
      // $form_state['redirect'] = 'feed/' . $feed->id();

      // Clear feed cache.
      Cache::invalidateTags(array('feeds' => TRUE));

      // Schedule jobs for this feed.
      $feed->schedule();
    }
    else {
      // In the unlikely case something went wrong on save, the feed will be
      // rebuilt and feed form redisplayed the same way as in preview.
      drupal_set_message($this->t('The feed could not be saved.'), 'error');
      $form_state->setRebuild();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $form, FormStateInterface $form_state) {
    $destination = array();
    $query = $this->getRequest()->query;

    if ($query->has('destination')) {
      $destination = drupal_get_destination();
      $query->remove('destination');
    }

    $feed = $this->entity;
    // $form_state['redirect'] = array('feed/' . $feed->id() . '/delete', array('query' => $destination));
  }

}
