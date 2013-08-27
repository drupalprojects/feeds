<?php

/**
 * @file
 * Contains \Drupal\feeds_job_scheduler\BackgroundScheduler.
 */

namespace Drupal\feeds_job_scheduler;

use Drupal\feeds\BatchScheduler;
use Drupal\feeds\StateInterface;
use Drupal\job_scheduler\JobControllerInterface;

/**
 * Executes an import or clear using Cron.
 */
class BackgroundScheduler extends BatchScheduler {

  /**
   * The translation manager service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $jobController;

  /**
   * Constructs a BackgroundScheduler object.
   *
   * @param \Drupal\job_scheduler\JobControllerInterface $job_controller
   *   The job_scheduler job controller.
   */
  public function __construct(JobControllerInterface $job_controller) {
    $this->jobController = $job_controller;
  }

  /**
   * {@inheritdoc}
   */
  public function startImport(FeedInterface $feed) {
    $this->startJob('import');
  }

  /**
   * {@inheritdoc}
   */
  public function startClear(FeedInterface $feed) {
    $this->startJob('clear');
  }

  /**
   * Starts a background job.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The Feed that is being executed.
   * @param string $method
   *   The method to run. 'import', 'clear', 'expire'
   */
  protected function startJob(FeedInterface $feed, $method) {
    if (StateInterface::BATCH_COMPLETE != $feed->$method()) {
      $job = array(
        'name' => "feeds_feed_{$method}",
        'type' => $feed->bundle(),
        'id' => $feed->id(),
        'period' => 0,
        'periodic' => FALSE,
      );
      $this->jobController->set($job);
    }
  }

  public function schedule(FeedInterface $feed) {
    $this->scheduleImport($feed);
    $this->scheduleExpire($feed);
  }

  /**
   * Schedule periodic or background import tasks.
   */
  public function scheduleImport(FeedInterface $feed) {
    // Check whether any fetcher is overriding the import period.
    $period = $feed->getImporter()->import_period;
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
    if ($period == FEEDS_SCHEDULE_NEVER) {
      $this->jobController->remove($job);
    }
    else {
      $this->jobController->set($job);
    }
  }

  /**
   * Schedule background expire tasks.
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
    if ($feed->getImporter()->getProcessor()->expiryTime() == FEEDS_EXPIRE_NEVER) {
      $this->jobController->remove($job);
    }
    else {
      $this->jobController->set($job);
    }
  }

  /**
   * Schedule background clearing tasks.
   */
  public function scheduleClear(FeedInterface $feed) {
    $job = array(
      'name' => 'feeds_feed_clear',
      'type' => $feed->bundle(),
      'id' => $feed->id(),
      'period' => 0,
      'periodic' => TRUE,
    );

    // Remove job if batch is complete.
    if ($feed->progressClearing() === StateInterface::BATCH_COMPLETE) {
      $this->jobController->remove($job);
    }
    // Schedule as soon as possible if batch is not complete.
    else {
      $this->jobController->set($job);
    }
  }

}
