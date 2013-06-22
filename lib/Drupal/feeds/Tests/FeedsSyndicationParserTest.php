<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\FeedsSyndicationParserTest
 */

namespace Drupal\feeds\Tests;

use Drupal\feeds\FeedsWebTestBase;

/**
 * Test single feeds.
 */
class FeedsSyndicationParserTest extends FeedsWebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Syndication parsers',
      'description' => 'Regression tests for the Common syndication parser. Tests parsers against a set of feeds in the context of Feeds module.',
      'group' => 'Feeds',
    );
  }

  /**
   * Run tests.
   */
  public function test() {
    $this->createImporterConfiguration('Syndication', 'syndication');

    $node_count = 0;
    foreach ($this->feedUrls() as $url => $assertions) {
      $fid = $this->createFeed('syndication', $url);
      $this->assertText('Created ' . $assertions['item_count'] . ' nodes');

      $count = db_query("SELECT COUNT(*) FROM {feeds_item} WHERE fid = :fid", array(':fid' => $fid))->fetchField();
      $this->assertEqual($count, $assertions['item_count'], t('@count feed items created.', array('@count' => $count)));

      $node_count += $assertions['item_count'];
      $count = db_query("SELECT COUNT(*) FROM {node}")->fetchField();
      $this->assertEqual($count, $node_count, t('@count nodes created.', array('@count' => $count)));
    }
  }

  /**
   * Return an array of test feeds.
   */
  protected function feedUrls() {
    $path = $GLOBALS['base_url'] . '/' . drupal_get_path('module', 'feeds') . '/tests/feeds/';
    return array(
      "{$path}developmentseed.rss2" => array(
        'item_count' => 10,
      ),
      "{$path}feed_without_guid.rss2" => array(
        'item_count' => 10,
      ),
    );
  }
}
