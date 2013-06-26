<?php

/**
 * @file
 * Contains Drupal\feeds\Tests\UserInterfaceTest.
 */

namespace Drupal\feeds\Tests;

use Drupal\feeds\FeedsWebTestBase;

/**
 * Tests basic Feeds UI functionality.
 */
class UserInterfaceTest extends FeedsWebTestBase {

  public static function getInfo() {
    return array(
      'name' => 'User interface',
      'description' => 'Tests Feeds Admin GUI.',
      'group' => 'Feeds',
    );
  }

  /**
   * UI functionality tests.
   */
  public function testEditFeedConfiguration() {

    // Create an importer.
    $this->createImporterConfiguration('Test feed', 'test_feed');

    // Assert UI elements.
    $this->drupalGet('admin/structure/feeds/manage/test_feed');
    $this->assertText('Basic settings');
    $this->assertText('Fetcher');
    $this->assertText('HTTP fetcher');
    $this->assertText('Parser');
    $this->assertText('Syndication parser');
    $this->assertText('Processor');
    $this->assertText('Content');
    $this->assertText('Getting started');
    $this->assertRaw('admin/structure/feeds/manage/test_feed/settings');
    $this->assertRaw('admin/structure/feeds/manage/test_feed/settings/processor');
    $this->assertRaw('admin/structure/feeds/manage/test_feed/fetcher');
    $this->assertRaw('admin/structure/feeds/manage/test_feed/parser');
    $this->assertRaw('admin/structure/feeds/manage/test_feed/processor');
    $this->drupalGet('feed/add');
    $this->assertText('Test feed');

    // Select some other plugins.
    $this->drupalGet('admin/structure/feeds/manage/test_feed');

    $this->clickLink('Change', 0);
    $this->assertText('Select a fetcher');
    $edit = array(
      'plugin_key' => 'upload',
    );
    $this->drupalPost('admin/structure/feeds/manage/test_feed/fetcher', $edit, 'Save');

    $this->clickLink('Change', 1);
    $this->assertText('Select a parser');
    $edit = array(
      'plugin_key' => 'csv',
    );
    $this->drupalPost('admin/structure/feeds/manage/test_feed/parser', $edit, 'Save');

    $this->clickLink('Change', 2);
    $this->assertText('Select a processor');
    $edit = array(
      'plugin_key' => 'entity:user',
    );
    $this->drupalPost('admin/structure/feeds/manage/test_feed/processor', $edit, 'Save');

    // Assert changed configuration.
    $this->assertPlugins('test_feed', 'upload', 'csv', 'entity:user');

    // Delete importer.
    $this->drupalPost('admin/structure/feeds/manage/test_feed/delete', array(), 'Delete');
    $this->drupalGet('feed/add');
    $this->assertNoText('Test feed');

    // Create the same importer again.
    $this->createImporterConfiguration('Test feed', 'test_feed');

    // Test basic settings settings.
    $edit = array(
      'name' => 'Syndication feed',
      'import_period' => 3600,
    );
    $this->setSettings('test_feed', NULL, $edit);

    // Assert results of change.
    $this->assertText('Syndication feed');
    $this->assertText('Your changes have been saved.');
    $this->assertText('Periodic import: every 1 hour');
    $this->drupalGet('admin/structure/feeds');

    // Configure processor.
    $this->setSettings('test_feed', 'processor', array('values[type]' => 'article'));
    $this->assertFieldByName('values[type]', 'article');

    // Create a feed node.
    $edit = array(
      'title' => 'Development Seed',
      'fetcher[source]' => $GLOBALS['base_url'] . '/' . drupal_get_path('module', 'feeds') . '/tests/feeds/developmentseed.rss2',
    );
    $this->drupalPost('feed/add/test_feed', $edit, 'Save');
    $this->assertText('Syndication feed Development Seed has been created.');

    // @todo Refreshing/deleting feed items. Needs to live in feeds.test
  }

}
