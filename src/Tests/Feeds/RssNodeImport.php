<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Feeds\RssNodeImport.
 */

namespace Drupal\feeds\Tests\Feeds;

use Drupal\Component\Utility\Unicode;
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
        ],
        // [
        //   'target' => 'body',
        //   'map' => ['value' => 'description'],
        //   'settings' => [
        //     'format' => 'basic_html',
        //   ],
        // ],
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
    $feed = entity_create('feeds_feed', [
      'title' => $this->randomString(),
      'source' => file_create_url(drupal_get_path('module', 'feeds') . '/tests/resources/googlenewstz.rss2'),
      'importer' => $this->importer->id(),
    ]);
    $feed->save();
    $this->drupalPostForm('feed/' . $feed->id() . '/import', [], t('Import'));
    $this->assertEqual(db_query("SELECT COUNT(*) FROM {node}")->fetchField(), 6);
    $titles = [
      1 => "First thoughts: Dems' Black Tuesday - msnbc.com",
      2 => 'Obama wants to fast track a final health care bill - USA Today',
      3 => 'Why the Nexus One Makes Other Android Phones Obsolete - PC World',
      4 => 'NEWSMAKER-New Japan finance minister a fiery battler - Reuters',
      5 => 'Yemen Detains Al-Qaeda Suspects After Embassy Threats - Bloomberg',
      6 => 'Egypt, Hamas exchange fire on Gaza frontier, 1 dead - Reuters',
    ];

    foreach (range(1, 6) as $nid) {
      $this->drupalGet('node/' . $nid . '/edit');
      $this->assertFieldByName('title[0][value]', $titles[$nid]);
    }
  }

}
