<?php

/**
 * @file
 * Definition of \Drupal\feeds\ImporterFormController.
 */

namespace Drupal\feeds;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityFormController;
use Drupal\feeds\FeedPluginFormInterface;

/**
 * Form controller for the importer edit forms.
 */
class ImporterFormController extends EntityFormController {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $config = $this->entity->getConfig();
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#description' => t('A human readable name of this importer.'),
      '#default_value' => $this->entity->label(),
      '#required' => TRUE,
    );
    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => t('Description'),
      '#description' => t('A description of this importer.'),
      '#default_value' => $this->entity->description,
    );
    $cron_required =  ' ' . l(t('Requires cron to be configured.'), 'http://drupal.org/cron', array('attributes' => array('target' => '_new')));
    $period = drupal_map_assoc(array(900, 1800, 3600, 10800, 21600, 43200, 86400, 259200, 604800, 2419200), 'format_interval');
    foreach ($period as &$p) {
      $p = t('Every !p', array('!p' => $p));
    }
    $period = array(
      FEEDS_SCHEDULE_NEVER => t('Off'),
      0 => t('As often as possible'),
    ) + $period;

    $form['import_period'] = array(
      '#type' => 'select',
      '#title' => t('Periodic import'),
      '#options' => $period,
      '#description' => t('Choose how often a source should be imported periodically.') . $cron_required,
      '#default_value' => $config['import_period'],
    );
    $form['import_on_create'] = array(
      '#type' => 'checkbox',
      '#title' => t('Import on submission'),
      '#description' => t('Check if import should be started at the moment a standalone form or node form is submitted.'),
      '#default_value' => $config['import_on_create'],
    );
    $form['process_in_background'] = array(
      '#type' => 'checkbox',
      '#title' => t('Process in background'),
      '#description' => t('For very large imports. If checked, import and delete tasks started from the web UI will be handled by a cron task in the background rather than by the browser. This does not affect periodic imports, they are handled by a cron task in any case.') . $cron_required,
      '#default_value' => $config['process_in_background'],
    );

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, array &$form_state) {
    $actions = parent::actions($form, $form_state);
    unset($actions['delete']);
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    // Build the importer object from the submitted values.
    $importer = parent::submit($form, $form_state);

    if ($this->entity->config['import_period'] != $form_state['values']['import_period']) {
      $importer->reschedule($importer->id());
    }
    $importer->addConfig($form_state['values']);

    return $importer;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    $importer = $this->entity;
    $importer->save();
    drupal_set_message(t('Your changes have been saved.'));
  }

}
