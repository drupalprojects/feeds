<?php

/**
 * @file
 * Tests for plugins/FeedsSitemapParser.inc
 */

namespace Drupal\feeds\Tests;

use Drupal\feeds\FeedsWebTestBase;

/**
 * Test Sitemap parser.
 */
class FeedsSitemapParserTest extends FeedsWebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Sitemap parser',
      'description' => 'Regression tests for Sitemap XML format parser.',
      'group' => 'Feeds',
    );
  }

  /**
   * Run tests.
   */
  public function test() {
    $this->createImporterConfiguration('Sitemap', 'sitemap');
    $this->setPlugin('sitemap', 'parser', 'sitemap');

    $this->addMappings('sitemap', array(
      0 => array(
        'source' => 'changefreq',
        'target' => 'title',
        'unique' => FALSE,
      ),
      1 => array(
        'source' => 'priority',
        'target' => 'body',
      ),
      2 => array(
        'source' => 'lastmod',
        'target' => 'created',
      ),
      3 => array(
        'source' => 'url',
        'target' => 'url',
        'unique' => TRUE,
      ),
      4 => array(
        'source' => 'url',
        'target' => 'guid',
        'unique' => TRUE,
      ),
    ));


    $path = $GLOBALS['base_url'] . '/' . drupal_get_path('module', 'feeds') . '/tests/feeds/';
    $fid = $this->createFeed('sitemap', $path . 'sitemap-example.xml', 'Testing Sitemap Parser');
    $this->assertText('Created 5 nodes');

    // Assert DB status.
    $count = db_query("SELECT COUNT(*) FROM {feeds_item} WHERE entity_type = 'node'")->fetchField();
    $this->assertEqual($count, 5, t('@count items in database.', array('@count' => $count)));

    // Check items against known content of feed.
    $items = db_query("SELECT * FROM {feeds_item} WHERE entity_type = 'node' AND fid = :fid ORDER BY entity_id", array(':fid' => $fid));

    // Check first item.
    date_default_timezone_set('GMT');
    $item = $items->fetchObject();
    $node = node_load($item->entity_id)->getNGEntity();
    $this->assertEqual($node->label(), 'monthly', 'Feed item 1 changefreq is correct.');
    $this->assertEqual($node->body->value, '0.8', 'Feed item 1 priority is correct.');
    $this->assertEqual($node->created->value, strtotime('2005-01-01'), 'Feed item 1 lastmod is correct.');
    $info = feeds_item_info_load('node', $node->id());
    $this->assertEqual($info->url, 'http://www.example.com/', 'Feed item 1 url is correct.');
    $this->assertEqual($info->url, $info->guid, 'Feed item 1 guid is correct.');

    // Check second item.
    $item = $items->fetchObject();
    $node = node_load($item->entity_id)->getNGEntity();
    $this->assertEqual($node->label(), 'weekly', 'Feed item 2 changefreq is correct.');
    $this->assertEqual($node->body->value, '', 'Feed item 2 priority is correct.');
    // $node->created->value is... recently
    $info = feeds_item_info_load('node', $node->id());
    $this->assertEqual($info->url, 'http://www.example.com/catalog?item=12&desc=vacation_hawaii', 'Feed item 2 url is correct.');
    $this->assertEqual($info->url, $info->guid, 'Feed item 2 guid is correct.');

    // Check third item.
    $item = $items->fetchObject();
    $node = node_load($item->entity_id)->getNGEntity();
    $this->assertEqual($node->label(), 'weekly', 'Feed item 3 changefreq is correct.');
    $this->assertEqual($node->body->value, '', 'Feed item 3 priority is correct.');
    $this->assertEqual($node->created->value, strtotime('2004-12-23'), 'Feed item 3 lastmod is correct.');
    $info = feeds_item_info_load('node', $node->id());
    $this->assertEqual($info->url, 'http://www.example.com/catalog?item=73&desc=vacation_new_zealand', 'Feed item 3 url is correct.');
    $this->assertEqual($info->url, $info->guid, 'Feed item 3 guid is correct.');

    // Check fourth item.
    $item = $items->fetchObject();
    $node = node_load($item->entity_id)->getNGEntity();
    $this->assertEqual($node->label(), '', 'Feed item 4 changefreq is correct.');
    $this->assertEqual($node->body->value, '0.3', 'Feed item 4 priority is correct.');
    $this->assertEqual($node->created->value, strtotime('2004-12-23T18:00:15+00:00'), 'Feed item 4 lastmod is correct.');
    $info = feeds_item_info_load('node', $node->id());
    $this->assertEqual($info->url, 'http://www.example.com/catalog?item=74&desc=vacation_newfoundland', 'Feed item 4 url is correct.');
    $this->assertEqual($info->url, $info->guid, 'Feed item 1 guid is correct.');

    // Check fifth item.
    $item = $items->fetchObject();
    $node = node_load($item->entity_id)->getNGEntity();
    $this->assertEqual($node->label(), '', 'Feed item 5 changefreq is correct.');
    $this->assertEqual($node->body->value, '', 'Feed item 5 priority is correct.');
    $this->assertEqual($node->created->value, strtotime('2004-11-23'), 'Feed item 5 lastmod is correct.');
    $info = feeds_item_info_load('node', $node->id());
    $this->assertEqual($info->url, 'http://www.example.com/catalog?item=83&desc=vacation_usa', 'Feed item 5 url is correct.');
    $this->assertEqual($info->url, $info->guid, 'Feed item 5 guid is correct.');

    // Check for more items.
    $item = $items->fetchObject();
    $this->assertFalse($item, 'Correct number of feed items recorded.');
  }

}
