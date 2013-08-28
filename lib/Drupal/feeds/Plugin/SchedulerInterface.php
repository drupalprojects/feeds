<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Scheduler\Periodic.
 */

namespace Drupal\feeds\Plugin;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\SchedulerInterface;

/**
 * Defines the Feeds scheduler plugin interface.
 */
interface SchedulerInterface extends FeedsPluginInterface {

  /**
   * Schedules a feed for import.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to schedule.
   */
  public function scheduleImport(FeedInterface $feed);

  /**
   * Schedules a feed for expire.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to schedule.
   */
  public function scheduleExpire(FeedInterface $feed);

}
