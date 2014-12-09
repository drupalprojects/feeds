<?php

/**
 * @file
 * Contains \Drupal\feeds\FeedForm.
 */

namespace Drupal\feeds;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\feeds\Plugin\Type\FeedPluginFormInterface;

/**
 * Form controller for the feed edit forms.
 */
class FeedForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $feed = $this->entity;

    $feed_type = $feed->getType();

    $args = ['@type' => $feed_type->label(), '@title' => $feed->label()];
    if ($this->operation === 'update') {
      $form['#title'] = $this->t('<em>Edit @type</em> @title', $args);
    }
    elseif ($this->operation === 'create') {
      $form['#title'] = $this->t('<em>Add @type</em>', $args);
    }

    $form['advanced'] = [
      '#type' => 'vertical_tabs',
      '#attributes' => ['class' => ['entity-meta']],
      '#weight' => 99,
    ];
    $form = parent::form($form, $form_state);

    $form['plugin']['#tree'] = TRUE;
    foreach ($feed_type->getPlugins() as $type => $plugin) {
      if ($plugin instanceof FeedPluginFormInterface) {
        $plugin_state = (new FormState())->setValues($form_state->getValue(['plugin', $type], []));
        $form['plugin'][$type] = $plugin->buildFeedForm([], $plugin_state, $feed);
        $form['plugin'][$type]['#tree'] = TRUE;

        $form_state->setValue(['plugin', $type], $plugin_state->getValues());
      }
    }

    $form['author'] = [
      '#type' => 'details',
      '#title' => $this->t('Authoring information'),
      '#group' => 'advanced',
      '#attributes' => ['class' => ['feeds-feed-form-author']],
      '#weight' => 90,
      '#optional' => TRUE,
    ];
    if (isset($form['uid'])) {
      $form['uid']['#group'] = 'author';
    }
    if (isset($form['created'])) {
      $form['created']['#group'] = 'author';
    }

    // Feed options for administrators.
    $form['options'] = [
      '#type' => 'details',
      '#access' => $this->currentUser()->hasPermission('administer feeds'),
      '#title' => $this->t('Import options'),
      '#collapsed' => TRUE,
      '#group' => 'advanced',
    ];

    $form['options']['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Active'),
      '#default_value' => $feed->isActive(),
    ];

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
    if ($form_state->getErrors()) {
      return;
    }
    $feed = $this->buildEntity($form, $form_state);

    foreach ($feed->getType()->getPlugins() as $type => $plugin) {
      if ($plugin instanceof FeedPluginFormInterface) {
        $plugin_state = (new FormState())->setValues($form_state->getValue(['plugin', $type], []));
        $plugin->validateFeedForm($form['plugin'][$type], $plugin_state, $feed);

        $form_state->setValue(['plugin', $type], $plugin_state->getValues());

        foreach ($plugin_state->getErrors() as $name => $error) {
          // Remove duplicate error messages.
          foreach ($_SESSION['messages']['error'] as $delta => $message) {
            if ($message['message'] === $error) {
              unset($_SESSION['messages']['error'][$delta]);
              break;
            }
          }
          $form_state->setErrorByName($name, $error);
        }
      }
    }

    parent::validate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Build the feed object from the submitted values.
    parent::submitForm($form, $form_state);
    $feed = $this->entity;

    foreach ($feed->getType()->getPlugins() as $type => $plugin) {
      if ($plugin instanceof FeedPluginFormInterface) {
        $plugin_state = (new FormState())->setValues($form_state->getValue(['plugin', $type], []));
        $plugin->submitFeedForm($form['plugin'][$type], $plugin_state, $feed);

        $form_state->setValue(['plugin', $type], $plugin_state->getValues());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $feed = $this->entity;
    $insert = $feed->isNew();
    $feed->save();

    $context = ['@type' => $feed->bundle(), '%title' => $feed->label()];
    $t_args = [
      '@type' => $feed->getType()->label(),
      '%title' => $feed->label(),
    ];

    if ($insert) {
      $this->logger('feeds')->notice('@type: added %title.', $context);
      drupal_set_message($this->t('%title has been created.', $t_args));
    }
    else {
      $this->logger('feeds')->notice('@type: updated %title.', $context);
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
      $form_state->setRedirect('entity.feeds_feed.canonical', ['feeds_feed' => $feed->id()]);
    }
    else {
      $form_state->setRedirect('<front>');
    }
  }

  /**
   * Form submission handler for the 'import' action.
   *
   * @param $form
   *   An associative array containing the structure of the form.
   * @param $form_state
   *   The current state of the form.
   */
  public function import(array $form, FormStateInterface $form_state) {
    $feed = $this->entity;
    $feed->startBatchImport();
    return $feed;
  }

}
