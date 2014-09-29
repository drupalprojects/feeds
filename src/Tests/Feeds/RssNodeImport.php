<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Feeds\RssNodeImport.
 */

namespace Drupal\feeds\Tests\Feeds;

use Drupal\Component\Utility\Unicode;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\simpletest\WebTestBase;

/**
 * Integration test that imports nodes from an RSS feed.
 *
 * @group Feeds
 */
class RssNodeImport extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'feeds'];

  protected function setUp() {
    parent::setUp();
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    $web_user = $this->drupalCreateUser(['administer feeds', 'bypass node access']);
    $this->drupalLogin($web_user);

    $this->importer = entity_create('feeds_importer', [
      'id' => Unicode::strtolower($this->randomMachineName()),
      'mappings' => [
        [
          'target' => 'title',
          'map' => ['value' => 'title'],
          'unique' => ['value' => TRUE],
        ],
        [
          'target' => 'body',
          'map' => ['value' => 'description'],
          // 'settings' => ['format' => 'basic_html'],
        ],
      ],
      'processor' => [
        'id' => 'entity:node',
        'configuration' => [
          'values' => [
            'type' => 'article',
          ],
        ],
      ],
    ]);
    $this->importer->save();
  }

  public function testHttpImport() {
    $filepath = drupal_get_path('module', 'feeds') . '/tests/resources/googlenewstz.rss2';

    $feed = entity_create('feeds_feed', [
      'title' => $this->randomString(),
      'source' => file_create_url($filepath),
      'importer' => $this->importer->id(),
    ]);
    $feed->save();
    $this->drupalPostForm('feed/' . $feed->id() . '/import', [], t('Import'));
    $this->assertText('Created 6');
    $this->assertEqual(db_query("SELECT COUNT(*) FROM {node}")->fetchField(), 6);

    $xml = new \SimpleXMLElement($filepath, 0, TRUE);

    foreach (range(1, 6) as $nid) {
      $item = $xml->channel->item[$nid - 1];
      $this->drupalGet('node/' . $nid . '/edit');
      $this->assertFieldByName('title[0][value]', (string) $item->title);
      $this->assertFieldByName('body[0][value]', (string) $item->description);
    }

    $this->drupalPostForm('feed/' . $feed->id() . '/import', [], t('Import'));
    $this->assertText('There are no new');
  }

}
