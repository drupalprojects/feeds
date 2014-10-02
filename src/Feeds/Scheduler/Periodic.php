<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Scheduler\Periodic.
 */

namespace Drupal\feeds\Feeds\Scheduler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\AdvancedFormPluginInterface;
use Drupal\feeds\Plugin\Type\ConfigurablePluginBase;
use Drupal\feeds\Plugin\Type\Scheduler\SchedulerInterface;

/**
 * Defines a Feeds scheduler plugin with optional periodic scheduling.
 *
 * @Plugin(
 *   id = "periodic",
 *   title = @Translation("Periodic"),
 *   description = @Translation("Schedules recurring imports.")
 * )
 */
class Periodic extends ConfigurablePluginBase implements SchedulerInterface, AdvancedFormPluginInterface {

  public function getImportPeriod() {
    return $this->configuration['import_period'];
  }

  /**
   * Schedules periodic or background import tasks.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to schedule.
   */
  public function scheduleImport(FeedInterface $feed) {

  }

  /**
   * Schedule background expire tasks.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to schedule.
   */
  public function scheduleExpire(FeedInterface $feed) {

  }

  /**
   * {@inheritdoc}
   */
  public function onFeedDeleteMultiple(array $feeds) {

  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildAdvancedForm(array $form, FormStateInterface $form_state) {
    // $cron_required = ' ' . l($this->t('Requires cron to be configured.'), 'http://drupal.org/cron', array('attributes' => array('target' => '_new')));
    $times = array(900, 1800, 3600, 10800, 21600, 43200, 86400, 259200, 604800, 2419200);
    $period = array_map(function($time) {
      return \Drupal::service('date.formatter')->formatInterval($time);
    }, array_combine($times, $times));

    foreach ($period as &$p) {
      $p = $this->t('Every !p', array('!p' => $p));
    }
    $period = array(
      SchedulerInterface::SCHEDULE_NEVER => $this->t('Off'),
      0 => $this->t('As often as possible'),
    ) + $period;

    $form['import_period'] = array(
      '#type' => 'select',
      '#title' => $this->t('Import period'),
      '#options' => $period,
      '#description' => $this->t('Choose how often a feed should be imported.'),
      '#default_value' => $this->configuration['import_period'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values =& $form_state->getValue(array('scheduler', 'configuration'));

    // Intentionally call parent last so that we can access our old values.
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array('import_period' => 3600);
  }

}
