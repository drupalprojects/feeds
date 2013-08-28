<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Scheduler\Periodic.
 */

namespace Drupal\feeds\Plugin\feeds\Scheduler;

use Drupal\Component\Annotation\Plugin;
use Drupal\Component\Utility\MapArray;
use Drupal\Core\Annotation\Translation;
use Drupal\feeds\AdvancedFormPluginInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\StateInterface;
use Drupal\feeds\Plugin\ConfigurablePluginBase;
use Drupal\feeds\Plugin\SchedulerInterface;

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

  /**
   * Constructs a Periodic object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Schedules periodic or background import tasks.
   *
   * This is also used as a callback for job_scheduler integration.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to schedule.
   */
  public function scheduleImport(FeedInterface $feed) {
    // Check whether any fetcher is overriding the import period.
    $period = $this->configuration['import_period'];

    $fetcher_period = $feed->getImporter()->getFetcher()->importPeriod($feed);

    if (is_numeric($fetcher_period)) {
      $period = $fetcher_period;
    }

    // Schedule as soon as possible if a batch is active.
    $period = $feed->progressImporting() === StateInterface::BATCH_COMPLETE ? $period : 0;

    $job = array(
      'name' => 'feeds_feed_import',
      'type' => $feed->bundle(),
      'id' => $feed->id(),
      'period' => $period,
      'periodic' => TRUE,
    );
    // if ($period == FEEDS_SCHEDULE_NEVER) {
    //   $this->jobController->remove($job);
    // }
    // else {
    //   $this->jobController->set($job);
    // }
  }

  /**
   * Schedule background expire tasks.
   *
   * This is also used as a callback for job_scheduler integration.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to schedule.
   */
  public function scheduleExpire(FeedInterface $feed) {
    // Schedule as soon as possible if a batch is active.
    $period = $feed->progressExpiring() === StateInterface::BATCH_COMPLETE ? 3600 : 0;

    $job = array(
      'name' => 'feeds_feed_expire',
      'type' => $feed->bundle(),
      'id' => $feed->id(),
      'period' => $period,
      'periodic' => TRUE,
    );
    // if ($feed->getImporter()->getProcessor()->expiryTime() == FEEDS_EXPIRE_NEVER) {
    //   $this->jobController->remove($job);
    // }
    // else {
    //   $this->jobController->set($job);
    // }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    return $form;
  }

  public function buildAdvancedForm(array $form, array &$form_state) {
    $cron_required = ' ' . l(t('Requires cron to be configured.'), 'http://drupal.org/cron', array('attributes' => array('target' => '_new')));
    $period = MapArray::copyValuesToKeys(array(900, 1800, 3600, 10800, 21600, 43200, 86400, 259200, 604800, 2419200), 'format_interval');

    foreach ($period as &$p) {
      $p = t('Every !p', array('!p' => $p));
    }
    $period = array(
      FEEDS_SCHEDULE_NEVER => t('Off'),
      0 => t('As often as possible'),
    ) + $period;

    $form['import_period'] = array(
      '#type' => 'select',
      '#title' => t('Import period'),
      '#options' => $period,
      '#description' => t('Choose how often a feed should be imported.') . $cron_required,
      '#default_value' => $this->configuration['import_period'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, array &$form_state) {
    $values =& $form_state['values']['scheduler']['configuration'];

    if ($this->configuration['import_period'] != $values['import_period']) {
      $this->importer->reschedule($this->importer->id());
    }

    // Intentionally call parent last so that we can access our old values.
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultConfiguration() {
    return array('import_period' => 3600);
  }

}
