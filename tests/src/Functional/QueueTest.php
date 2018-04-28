<?php

namespace Drupal\Tests\feeds\Functional;

/**
 * Tests behavior involving the queue.
 *
 * @group feeds
 */
class QueueTest extends FeedsBrowserTestBase {

  /**
   * Tests if a feed gets imported via cron after adding it to the queue.
   */
  public function testCronImport() {
    $feed_type = $this->createFeedType();

    // Create a feed and ensure it gets on cron.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss2',
    ]);
    $feed->startCronImport();

    // Run cron to import.
    $this->cronRun();

    // Assert that 6 nodes have been created.
    $this->assertNodeCount(6);
  }

}
