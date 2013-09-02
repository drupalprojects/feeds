<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Plugin\SchedulerInterface.
 */

namespace Drupal\feeds\Plugin;

use Drupal\feeds\FeedInterface;

/**
 * Defines the Feeds scheduler plugin interface.
 */
interface SchedulerInterface extends FeedsPluginInterface {

  /**
   * Never expire feed items.
   *
   * @var int
   */
  const EXPIRE_NEVER = -1;

  /**
   * Do not schedule a feed for refresh.
   *
   * @var int
   */
  const SCHEDULE_NEVER = -1;

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
