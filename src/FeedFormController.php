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
use Drupal\Core\Url;
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
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);

    // Add an "Import" button.
    if ($this->entity->access('import')) {
      $element['submit']['#dropbutton'] = 'save';
      $element['import'] = $element['submit'];
      $element['import']['#dropbutton'] = 'save';
      $element['import']['#value'] = t('Save and import');
      $element['import']['#weight'] = 0;
      $element['import']['#submit'][] = '::import';
    }

    $element['delete']['#access'] = $this->entity->access('delete');

    return $element;
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
    $insert = $feed->isNew();
    $feed->save();

    $context = ['@importer' => $feed->getImporter()->id(), '%title' => $feed->label(), 'link' => $feed->link($this->t('View'))];
    $t_args = ['@importer' => $feed->getImporter()->label(), '%title' => $feed->label()];

    if ($insert) {
      $this->logger('feeds')->notice('@importer: added %title.', $context);
      drupal_set_message($this->t('%title has been created.', $t_args));
    }
    else {
      $this->logger('feeds')->notice('@importer: updated %title.', $context);
      drupal_set_message($this->t('%title has been updated.', $t_args));
    }

    if (!$feed->id()) {
      // In the unlikely case something went wrong on save, the feed will be
      // rebuilt and feed form redisplayed the same way as in preview.
      drupal_set_message($this->t('The feed could not be saved.'), 'error');
      $form_state->setRebuild();
      return;
    }

    if ($feed->access('view')) {
      $form_state->setRedirect('feeds.view', ['feeds_feed' => $feed->id()]);
    }
    else {
      $form_state->setRedirect('<front>');
    }
  }

}
