<?php

/**
 * @file
 * Tests for plugins/FeedsNodeProcessor.inc.
 */

namespace Drupal\feeds\Tests;

use Drupal\Component\Utility\String;
use Drupal\feeds\FeedsWebTestBase;
use Drupal\feeds\Plugin\Type\Processor\ProcessorInterface;

/**
 * Test aggregating a feed as node items.
 */
class FeedsRSStoNodesTest extends FeedsWebTestBase {

  public static function getInfo() {
    return array(
      'name' => 'RSS import to nodes',
      'description' => 'Tests a feed configuration that is attached to a content type, uses HTTP fetcher, common syndication parser and a node processor. Repeats the same test for an importer configuration that is not attached to a content type and for a configuration that is attached to a content type and uses the file fetcher.',
      'group' => 'Feeds',
    );
  }

  /**
   * Set up test.
   */
  public function setUp() {
    parent::setUp();

    // Set the front page to show 20 nodes so we can easily see what is aggregated.
    variable_set('default_nodes_main', 20);

    // Set the teaser length display to unlimited otherwise tests looking for
    // text on nodes will fail.
    $edit = array('fields[body][type]' => 'text_default');
    $this->drupalPost('admin/structure/types/manage/article/display/teaser', $edit, 'Save');

    // Create an importer configuration.
    $this->createImporterConfiguration('Syndication', 'syndication');

    drupal_flush_all_caches();

    $this->addMappings('syndication', array(
      0 => array(
        'target' => 'title',
        'map' => array(
          'value' => 'title',
        ),
      ),
      1 => array(
        'target' => 'body',
        'map' => array(
          'value' => 'description',
        ),
      ),
      2 => array(
        'target' => 'created',
        'map' => array(
          'value' => 'timestamp',
        ),
      ),
      3 => array(
        'target' => 'feeds_item',
        'map' => array(
          'url' => 'url',
          'guid' => 'guid',
        ),
        'unique' => array(
          'url' => 1,
          'guid' => 1,
        ),
      ),
    ));
  }

  /**
   * Test node creation, refreshing/deleting feeds and feed items.
   */
  public function test() {
    $fid = $this->createFeed();

    // Assert 10 items aggregated after creation of the node.
    $this->assertText('Created 10 ');
    $article_nid = db_query_range("SELECT nid FROM {node} WHERE type = 'article'", 0, 1)->fetchField();
    $this->assertEqual("Created by FeedsNodeProcessor", db_query("SELECT nr.log FROM {node} n JOIN {node_field_revision} nr ON n.vid = nr.vid WHERE n.nid = :nid", array(':nid' => $article_nid))->fetchField());

    // Navigate to feed node, there should be Feeds tabs visible.
    $this->drupalGet("feed/$fid");
    $this->assertRaw("feed/$fid/import");
    $this->assertRaw("feed/$fid/delete-items");

    // Assert accuracy of aggregated information.
    $this->drupalGet('node');
    $this->assertRaw('<span>Anonymous (not verified)</span>');
    $this->assertDevseedFeedContent();

    // Assert DB status.
    $count = db_query("SELECT COUNT(*) FROM {node} n INNER JOIN {node__feeds_item} fi ON n.nid = fi.entity_id WHERE feeds_item_target_id = :fid", array(':fid' => $fid))->fetchField();
    $this->assertEqual($count, 10, 'Accurate number of items in database.');

    // Assert default input format on first imported feed node.

    // NEEDS update.
    // $format = db_query_range("SELECT nr.format FROM {feeds_node_item} fi JOIN {node} n ON fi.nid = n.nid JOIN {node_revision} nr ON n.vid = nr.vid", 0, 1)->fetchField();
    // $this->assertEqual($format, filter_fallback_format(), 'Using default Input format.');

    // Import again.
    $this->feedImportItems($fid);
    $this->assertText('There are no new ');

    // Assert DB status, there still shouldn't be more than 10 items.
    $count = db_query("SELECT COUNT(*) FROM {node} n INNER JOIN {node__feeds_item} fi ON n.nid = fi.entity_id")->fetchField();
    $this->assertEqual($count, 10, 'Accurate number of items in database.');

    // All of the above tests should have produced published nodes, set default
    // to unpublished, import again.
    $count = db_query("SELECT COUNT(*) FROM {node_field_data} n INNER JOIN {node__feeds_item} fi ON n.nid = fi.entity_id WHERE n.status = 1")->fetchField();
    $this->assertEqual($count, 10, 'All items are published.');
    $edit = array(
      'settings[node][options][status]' => FALSE,
    );
    $this->drupalPost('admin/structure/types/manage/article', $edit, t('Save content type'));
    $this->feedDeleteItems($fid);
    $this->feedImportItems($fid);
    $count = db_query("SELECT COUNT(*) FROM {node_field_data} n INNER JOIN {node__feeds_item} fi ON n.nid = fi.entity_id WHERE n.status = 0")->fetchField();
    $this->assertEqual($count, 10, String::format('@count items are unpublished.', array('@count' => $count)));
    $edit = array(
      'settings[node][options][status]' => TRUE,
    );
    $this->drupalPost('admin/structure/types/manage/article', $edit, t('Save content type'));
    $this->feedDeleteItems($fid);

    // Enable replace existing and import updated feed file.
    $this->feedImportItems($fid);
    $this->setSettings('syndication', 'processor', array('update_existing' => 1));
    $feed_url = $GLOBALS['base_url'] . '/' . drupal_get_path('module', 'feeds') . '/tests/feeds/developmentseed_changes.rss2';
    $this->editFeed($fid, $feed_url);
    $this->feedImportItems($fid);
    $this->assertText('Updated 2 ');

    // Assert accuracy of aggregated content (check 2 updates, one original).
    $this->drupalGet('node');
    $this->assertText('Managing News Translation Workflow: Two Way Translation Updates');
    $this->assertText('Presenting on Features in Drupal and Managing News');
    $this->assertText('Scaling the Open Atrium UI');

    // Import again.
    $this->feedImportItems($fid);
    $this->assertText('There are no new ');
    $this->assertFeedItemCount(10);

    // Now delete all items.
    $this->feedDeleteItems($fid);
    $this->assertText('Deleted 10 ');
    $this->assertFeedItemCount(0);

    // Change author and turn off authorization.
    $this->auth_user = $this->drupalCreateUser(array('access content'));
    $this->setSettings('syndication', 'processor', array(
      'author' => $this->auth_user->getUsername(),
      'authorize' => FALSE,
    ));

    // Change input format.
    drupal_flush_all_caches();
    $importer = entity_load('feeds_importer', 'syndication', TRUE);
    $importer->getTargetPlugin(1)->setConfiguration(array('format' => 'plain_text'));
    $importer->save();

    // Import again.
    $this->feedImportItems($fid);
    $this->assertText('Created 10 ');

    // Assert author.
    $this->drupalGet('node');
    $this->assertPattern('/<span>' . check_plain($this->auth_user->getUsername()) . '<\/span>/');
    $count = db_query("SELECT COUNT(*) FROM {node__feeds_item} fi INNER JOIN {node_field_data} n ON fi.entity_id = n.nid WHERE n.uid = :uid", array(':uid' => $this->auth_user->uid->value))->fetchField();
    $this->assertEqual($count, 10, format_string('@count items in database.', array('@count' => $count)));

    // Assert input format.

    // NEEDS update.
    // $format = db_query_range("SELECT nr.format FROM {feeds_node_item} fi JOIN {node} n ON fi.nid = n.nid JOIN {node_revision} nr ON n.vid = nr.vid", 0, 1)->fetchField();
    // $this->assertEqual($format, filter_fallback_format() + 1, 'Set non-default Input format.');

    // Set to update existing, remove authorship of above nodes and import again.
    $this->setSettings('syndication', 'processor', array('update_existing' => ProcessorInterface::UPDATE_EXISTING));

    $nids = db_query("SELECT nid FROM {node} n INNER JOIN {node__feeds_item} fi ON n.nid = fi.entity_id")->fetchCol();
    foreach ($nids as $nid) {
      $node = entity_load('node', $nid, TRUE);
      $node->setAuthorId(0);
      $node->save();
    }
    // $feed = entity_load('feeds_feed', $fid, TRUE);
    // $feed->setAuthorId(0);
    // $feed->save();

    db_update('node__feeds_item')
      ->fields(array('feeds_item_hash' => ''))
      ->condition('entity_id', $nids)
      ->execute();

    $this->feedImportItems($fid);

    $this->drupalGet('node');
    $this->assertNoPattern('/<span>' . check_plain($this->auth_user->getUsername()) . '<\/span>/');
    $count = db_query("SELECT COUNT(*) FROM {node__feeds_item} fi JOIN {node_field_data} n ON fi.entity_id = n.nid WHERE n.uid = :uid", array(':uid' => $this->auth_user->id()))->fetchField();
    $this->assertEqual($count, 0, format_string('@count items in database.', array('@count' => $count)));

    // Map feed's author to feed item author, update - feed node's items
    // should now be assigned to feed node author.
    $this->addMappings('syndication', array(
      5 => array(
        'target' => 'uid',
        'map' => array(
          'target_id' => 'parent:uid',
        ),
        'settings' => array(
          'reference_by' => 'uid',
        ),
      ),
    ));
    $this->feedImportItems($fid);
    $this->drupalGet('node');
    $this->assertNoPattern('/<span>' . check_plain($this->auth_user->getUsername()) . '<\/span>/');
    $uid = db_query("SELECT uid FROM {feeds_feed} WHERE fid = :fid", array(':fid' => $fid))->fetchField();
    $count = db_query("SELECT COUNT(*) FROM {node_field_data} WHERE uid = :uid", array(':uid' => $uid))->fetchField();
    $this->assertEqual($count, 10, format_string('@count feed item nodes are assigned to feed node author.', array('@count' => $count)));

    // Login with new user with only access content permissions.
    $this->drupalLogin($this->auth_user);

    // Navigate to feed node, there should be no Feeds tabs visible.
    $this->drupalGet("feed/$fid");
    $this->assertNoRaw("feed/$fid/import");
    $this->assertNoRaw("feed/$fid/delete-items");

    // Now create a second feed configuration that is not attached to a content
    // type and run tests on importing/purging.

    // Login with sufficient permissions.
    $this->drupalLogin($this->admin_user);
    // Remove all items again so that next test can check for them.
    $this->feedDeleteItems($fid);

    // Create an importer, not attached to content type.
    $this->createImporterConfiguration('Syndication standalone', 'syndication_standalone');
    $this->addMappings('syndication_standalone', array(
      0 => array(
        'target' => 'title',
        'map' => array(
          'value' => 'title',
        ),
      ),
      1 => array(
        'target' => 'body',
        'map' => array(
          'value' => 'description',
        ),
      ),
      2 => array(
        'target' => 'created',
        'map' => array(
          'value' => 'timestamp',
        ),
      ),
      3 => array(
        'target' => 'feeds_item',
        'map' => array(
          'url' => 'url',
          'guid' => 'guid',
        ),
        'unique' => array(
          'url' => 1,
          'guid' => 1,
        ),
      ),
    ));

    drupal_flush_all_caches();
    // Import, assert 10 items aggregated after creation of the node.
    $fid = $this->importURL('syndication_standalone');
    $this->assertText('Created 10 ');

    // Assert accuracy of aggregated information.
    $this->drupalGet('node');
    $this->assertDevseedFeedContent();
    $this->assertFeedItemCount(10);

    // Import again.
    $this->feedImportItems($fid);
    $this->assertText('There are no new ');
    $this->assertFeedItemCount(10);

    // Enable replace existing and import updated feed file.
    $this->setSettings('syndication_standalone', 'processor', array('update_existing' => ProcessorInterface::REPLACE_EXISTING));
    $feed_url = $GLOBALS['base_url'] . '/' . drupal_get_path('module', 'feeds') . '/tests/feeds/developmentseed_changes.rss2';
    $this->importURL('syndication_standalone', $feed_url, $fid);
    $this->assertText('Updated 2 ');

    // Assert accuracy of aggregated information (check 2 updates, one orig).
    $this->drupalGet('node');
    $this->assertText('Managing News Translation Workflow: Two Way Translation Updates');
    $this->assertText('Presenting on Features in Drupal and Managing News');
    $this->assertText('Scaling the Open Atrium UI');

    // Import again.
    $this->feedImportItems($fid);
    $this->assertText('There are no new ');
    $this->assertFeedItemCount(10);

    // Now delete all items.
    $this->feedDeleteItems($fid);
    $this->assertText('Deleted 10 ');
    $this->assertFeedItemCount(0);

    // Import again, we should find new content.
    $this->feedImportItems($fid);
    $this->assertText('Created 10 ');
    $this->assertFeedItemCount(10);

    // Login with new user with only access content permissions.
    $this->drupalLogin($this->auth_user);

    // Navigate to feed import form, access should be denied.
    $this->drupalGet("feed/$fid/import");
    $this->assertResponse(403);

    // Use File Fetcher.
    $this->drupalLogin($this->admin_user);

    $this->setPlugin('syndication_standalone', 'fetcher', 'upload');
    $this->setSettings('syndication_standalone', 'fetcher', array('allowed_extensions' => 'rss2'));

    // Create a feed node.
    $edit = array(
      'files[fetcher_upload]' => $this->absolutePath() . '/tests/feeds/drupalplanet.rss2',
    );
    $this->drupalPost("feed/$fid/edit", $edit, 'Save');
    $this->feedImportItems($fid);
    $this->assertText('Created 25 ');
  }

  /**
   * Check that the total number of entries in the feeds_item table is correct.
   */
  function assertFeedItemCount($num) {
    $count = db_query("SELECT COUNT(*) FROM {node__feeds_item}")->fetchField();
    $this->assertEqual($count, $num, 'Accurate number of items in database.');
  }

  /**
   * Check thet contents of the current page for the DS feed.
   */
  function assertDevseedFeedContent() {
    $this->assertText('Open Atrium Translation Workflow: Two Way Translation Updates');
    $this->assertText('Tue, 10/06/2009');
    $this->assertText('A new translation process for Open Atrium &amp; integration with Localize Drupal');
    $this->assertText('Week in DC Tech: October 5th Edition');
    $this->assertText('Mon, 10/05/2009');
    $this->assertText('There are some great technology events happening this week');
    $this->assertText('Mapping Innovation at the World Bank with Open Atrium');
    $this->assertText('Fri, 10/02/2009');
    $this->assertText('is being used as a base platform for collaboration at the World Bank');
    $this->assertText('September GeoDC Meetup Tonight');
    $this->assertText('Wed, 09/30/2009');
    $this->assertText('Today is the last Wednesday of the month');
    $this->assertText('Week in DC Tech: September 28th Edition');
    $this->assertText('Mon, 09/28/2009');
    $this->assertText('Looking to geek out this week? There are a bunch of');
    $this->assertText('Open Data for Microfinance: The New MIXMarket.org');
    $this->assertText('Thu, 09/24/2009');
    $this->assertText('There are profiles for every country that the MIX Market is hosting.');
    $this->assertText('Integrating the Siteminder Access System in an Open Atrium-based Intranet');
    $this->assertText('Tue, 09/22/2009');
    $this->assertText('In addition to authentication, the Siteminder system');
    $this->assertText('Week in DC Tech: September 21 Edition');
    $this->assertText('Mon, 09/21/2009');
    $this->assertText('an interesting variety of technology events happening in Washington, DC ');
    $this->assertText('s Software Freedom Day: Impressions &amp; Photos');
    $this->assertText('Mon, 09/21/2009');
    $this->assertText('Presenting on Features in Drupal and Open Atrium');
    $this->assertText('Scaling the Open Atrium UI');
    $this->assertText('Fri, 09/18/2009');
    $this->assertText('The first major change is switching');
  }

  /**
   * Test validation of feed URLs.
   */
  function testFeedURLValidation() {
    $edit = array(
      'fetcher[source]' => 'invalid://url',
    );
    $this->drupalPost('feed/add/syndication', $edit, 'Save');
    $this->assertText('The URL invalid://url is invalid.');
  }

  /**
   * Test using non-normal URLs like feed:// and webcal://.
   *
   * @todo Fix auto title.
   */
  function testOddFeedSchemes() {
    $url = $GLOBALS['base_url'] . '/' . drupal_get_path('module', 'feeds') . '/tests/feeds/developmentseed.rss2';

    $schemes = array('feed', 'webcal');
    $item_count = 0;
    foreach ($schemes as $scheme) {
      $feed_url = strtr($url, array('http://' => $scheme . '://', 'https://' => $scheme . '://'));

      $edit['fetcher[source]'] = $feed_url;
      $edit['title'] = 'Development Seed - Technological Solutions for Progressive Organizations has been created.';
      $this->drupalPost('feed/add/syndication', $edit, 'Save');
      $this->assertText('Development Seed - Technological Solutions for Progressive Organizations has been created.');
      $this->assertText('Created 10 ');
      $this->assertFeedItemCount($item_count + 10);
      $item_count += 10;
      drupal_flush_all_caches();
    }
  }

  /**
   * Test that nodes will not be created if the user is unauthorized to create
   * them.
   *
   * @todo User ref by name.
   */
  public function testAuthorize() {

    // Create a user with limited permissions.
   $account = $this->drupalCreateUser(array(), 'Development Seed');

    // Adding a mapping to the user_name will invoke authorization.
    $this->addMappings('syndication', array(
      5 => array(
        'target' => 'uid',
        'map' => array(
          'target_id' => 'author_name',
        ),
        'settings' => array(
          'reference_by' => 'name',
        ),
      ),
    ));

    $fid = $this->createFeed();

    $this->assertText('Failed importing 10 ');
    $this->assertText('User ' . $account->name->value . ' is not authorized to create content type article.');
    $node_count = db_query("SELECT COUNT(*) FROM {node}")->fetchField();

    // We should have 0 nodes.
    $this->assertEqual($node_count, 0, format_string('@count of nodes in the database.', array('@count' => $node_count)));

    // Give the user super admin powers.
    user_delete($account->uid->value);
    $account = $this->drupalCreateUser(array('access content', 'bypass node access'), 'Development Seed');

    drupal_flush_all_caches();
    $this->feedImportItems($fid);
    $this->assertText('Created 10 ');
    $node_count = db_query("SELECT COUNT(*) FROM {node}")->fetchField();
    $this->assertEqual($node_count, 10, format_string('@count of nodes in the database.', array('@count' => $node_count)));
  }

  /**
   * Tests expiring nodes.
   */
  public function testExpiry() {
    // Create importer configuration.
    $this->setSettings('syndication', 'processor', array(
      'expire' => 3600,
    ));

    // Create importer.
    $this->importURL('syndication');
    $node_count = db_query('SELECT COUNT(*) FROM {node__feeds_item}')->fetchField();

    // Set date of a few nodes to current date so they don't expire.
    $edit = array(
      'date' => date('Y-m-d H:i:s'),
    );
    $this->drupalPost('node/2/edit', $edit, 'Save and keep published');
    $this->assertText(date('m/d/Y'), 'Found correct date.');
    $this->drupalPost('node/5/edit', $edit, 'Save and keep published');
    $this->assertText(date('m/d/Y'), 'Found correct date.');

    // Run cron to schedule jobs.
    $this->cronRun();

    // Set feeds source expire to run immediately.
    db_update('job_schedule')
      ->fields(array(
        'next' => 0,
      ))
      ->condition('name', 'feeds_feed_expire')
      ->execute();

    // Run cron to execute scheduled jobs.
    $this->cronRun();

    // Query the feeds_items table and count the number of entries.
    $row_count = db_query('SELECT COUNT(*) FROM {node__feeds_item}')->fetchField();

    // Check that number of feeds items is equal to the expected items.
    $this->assertEqual($row_count, 2, format_string('@count nodes expired.', array('@count' => $node_count - $row_count)));
  }

}
