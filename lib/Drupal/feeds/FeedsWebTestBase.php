<?php

/**
 * @file
 * Common functionality for all Feeds tests.
 */

namespace Drupal\feeds;

use Drupal\simpletest\WebTestBase;
use Drupal\feeds\Plugin\FeedsPlugin;

/**
 * Test basic Data API functionality.
 */
class FeedsWebTestBase extends WebTestBase {

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'field',
    'field_ui',
    'taxonomy',
    'file',
    'image',
    'job_scheduler',
    'feeds',
    'feeds_ui',
    'feeds_tests',
    'views',
  );

  public function setUp() {
    parent::setUp();

    // Create text format.
    $filtered_html_format = entity_create('filter_format', array(
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
      'filters' => array(
        // URL filter.
        'filter_url' => array(
          'weight' => 0,
          'status' => 1,
        ),
        // HTML filter.
        'filter_html' => array(
          'weight' => 1,
          'status' => 1,
        ),
        // Line break filter.
        'filter_autop' => array(
          'weight' => 2,
          'status' => 1,
        ),
        // HTML corrector filter.
        'filter_htmlcorrector' => array(
          'weight' => 10,
          'status' => 1,
        ),
      ),
    ));

    $filtered_html_format->save();

    $permissions = array();
    $permissions[] = 'access content';
    $permissions[] = 'administer site configuration';
    $permissions[] = 'administer content types';
    $permissions[] = 'administer nodes';
    $permissions[] = 'bypass node access';
    $permissions[] = 'administer taxonomy';
    $permissions[] = 'administer users';
    $permissions[] = 'administer feeds';
    $permissions[] = 'administer node fields';
    $permissions[] = 'administer node display';
    $permissions[] = 'administer feeds_feed fields';
    $permissions[] = 'administer feeds_feed display';

    // Create an admin user and log in.
    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);

    $types = array(
      array(
        'type' => 'page',
        'name' => 'Basic page',
      ),
      array(
        'type' => 'article',
        'name' => 'Article',
      ),
    );
    foreach ($types as $type) {
      $this->drupalCreateContentType($type);
      $edit = array(
        'node_options[status]' => 1,
        'node_options[promote]' => 1,
      );
      $this->drupalPost('admin/structure/types/manage/' . $type['type'], $edit, 'Save content type');
    }

    $display = config('views.view.frontpage')->get('display');
    $display['default']['display_options']['pager']['options']['items_per_page'] = 500;
    config('views.view.frontpage')
      ->set('display', $display)
      ->save();
  }

  /**
   * Absolute path to Drupal root.
   */
  public function absolute() {
    return realpath(getcwd());
  }

  /**
   * Get the absolute directory path of the feeds module.
   */
  public function absolutePath() {
    return  $this->absolute() . '/' . drupal_get_path('module', 'feeds');
  }

  /**
   * Generate an OPML test feed.
   *
   * The purpose of this function is to create a dynamic OPML feed that points
   * to feeds included in this test.
   */
  public function generateOPML() {
    $path = $GLOBALS['base_url'] . '/' . drupal_get_path('module', 'feeds') . '/tests/feeds/';

  $output =
'<?xml version="1.0" encoding="utf-8"?>
<opml version="1.1">
<head>
    <title>Feeds test OPML</title>
    <dateCreated>Fri, 16 Oct 2009 02:53:17 GMT</dateCreated>
    <ownerName></ownerName>
</head>
<body>
  <outline text="Feeds test group" >
    <outline title="Development Seed - Technological Solutions for Progressive Organizations" text="" xmlUrl="' . $path . 'developmentseed.rss2" type="rss" />
    <outline title="Magyar Nemzet Online - H\'rek" text="" xmlUrl="' . $path . 'feed_without_guid.rss2" type="rss" />
    <outline title="Drupal planet" text="" type="rss" xmlUrl="' . $path . 'drupalplanet.rss2" />
  </outline>
</body>
</opml>';

    // UTF 8 encode output string and write it to disk
    $output = utf8_encode($output);
    $filename = file_default_scheme() . '://test-opml-' . $this->randomName() . '.opml';

    $filename = file_unmanaged_save_data($output, $filename);
    return $filename;
  }

  /**
   * Create an importer configuration.
   *
   * @param $name
   *   The natural name of the feed.
   * @param $id
   *   The persistent id of the feed.
   * @param $edit
   *   Optional array that defines the basic settings for the feed in a format
   *   that can be posted to the feed's basic settings form.
   */
  public function createImporterConfiguration($name = 'Syndication', $id = 'syndication') {
    // Create new feed configuration.
    $this->drupalGet('admin/structure/feeds');
    $this->clickLink('Add importer');
    $edit = array(
      'name' => $name,
      'id' => $id,
    );
    $this->drupalPost('admin/structure/feeds/create', $edit, 'Create');

    // Assert message and presence of default plugins.
    $this->assertText('Your configuration has been created with default settings.');
    $this->assertPlugins($id, 'http', 'syndication', 'node');
    // Per default attached to article content type.
    $this->setSettings($id, 'processor', array('bundle' => 'article'));
  }

  /**
   * Choose a plugin for a importer configuration and assert it.
   *
   * @param $id
   *   The importer configuration's id.
   * @param $plugin_key
   *   The key string of the plugin to choose (one of the keys defined in
   *   feeds_feeds_plugins()).
   */
  public function setPlugin($id, $type, $plugin_key) {
    $edit = array(
      'plugin_key' => $plugin_key,
    );
    $this->drupalPost("admin/structure/feeds/manage/$id/$type", $edit, 'Save');

    // Assert actual configuration.
    $config = config('feeds.importer.' . $id)->get('config');
    $this->assertEqual($config[$type]['plugin_key'], $plugin_key, 'Verified correct ' . $type . ' (' . $plugin_key . ').');
  }

  /**
   * Set importer or plugin settings.
   *
   * @param $id
   *   The importer configuration's id.
   * @param $plugin
   *   The plugin (class) name, or NULL to set importer's settings
   * @param $settings
   *   The settings to set.
   */
  public function setSettings($id, $plugin_type, $settings) {
    $this->drupalPost('admin/structure/feeds/manage/' . $id . '/settings/' . $plugin_type, $settings, 'Save');
    $this->assertText('Your changes have been saved.');
  }

  /**
   * Create a test feed node. Test user has to have sufficient permissions:
   *
   * * create [type] content
   * * use feeds
   *
   * Assumes that page content type has been configured with
   * createImporterConfiguration() as a feed content type.
   *
   * @return
   *   The node id of the node created.
   */
  public function createFeed($id = 'syndication', $feed_url = NULL, $title = '') {
    if (empty($feed_url)) {
      $feed_url = $GLOBALS['base_url'] . '/' . drupal_get_path('module', 'feeds') . '/tests/feeds/developmentseed.rss2';
    }

    if (!$title) {
      $title = $this->randomString();
    }

    // Create a feed node.
    $edit = array(
      'title' => $title,
      'fetcher[source]' => $feed_url,
    );
    $this->drupalPost('feed/add/' . $id, $edit, 'Save');
    $this->assertText('has been created.');

    // Get the node id from URL.
    $fid = $this->getFid($this->getUrl());

    // Check whether feed got recorded in feeds_feed table.
    $query = db_select('feeds_feed', 'f')
      ->condition('f.importer', $id, '=')
      ->condition('f.fid', $fid, '=');

    $query->addExpression("COUNT(*)");
    $result = $query->execute()->fetchField();
    $this->assertEqual(1, $result);

    $source = db_select('feeds_feed', 'f')
      ->condition('f.importer', $id, '=')
      ->condition('f.fid', $fid, '=')
      ->fields('f', array('config'))
      ->execute()
      ->fetchObject();

    $config = unserialize($source->config);

    $plugin_type = isset($config['http']) ? 'http' : 'file';

    $this->assertEqual($config[$plugin_type]['source'], $feed_url, t('URL in DB correct.'));

    return $fid;
  }

  /**
   * Edit the configuration of a feed node to test update behavior.
   *
   * @param $fid
   *   The fid to edit.
   * @param $feed_url
   *   The new (absolute) feed URL to use.
   * @param $title
   *   Optional parameter to change title of feed node.
   */
  public function editFeed($fid, $feed_url, $title = '') {
    if (!$title) {
      $title = $this->randomString();
    }
    $edit = array(
      'title' => $title,
      'fetcher[source]' => $feed_url,
    );
    // Check that the update was saved.
    $this->drupalPost("feed/$fid/edit", $edit, 'Save');
    $this->assertText('has been updated.');

    // Check that the URL was updated in the feeds_feed table.
    $feed = db_query("SELECT * FROM {feeds_feed} WHERE fid = :fid", array(':fid' => $fid))->fetchObject();
    $config = unserialize($feed->config);

    $plugin_type = isset($config['http']) ? 'http' : 'file';

    $this->assertEqual($config[$plugin_type]['source'], $feed_url, t('URL in DB correct.'));
  }

  /**
   * Batch create a variable amount of feed nodes. All will have the
   * same URL configured.
   *
   * @return
   *   An array of node ids of the nodes created.
   */
  public function createFeeds($id = 'syndication', $num = 20) {
    $fids = array();
    for ($i = 0; $i < $num; $i++) {
      $fids[] = $this->createFeed($id, NULL, $this->randomName());
    }

    return $fids;
  }

  /**
   * Import a URL through the import form. Assumes http, or file in place.
   */
  public function importURL($id, $feed_url = NULL, $fid = NULL) {
    if (!$feed_url) {
      $feed_url = $GLOBALS['base_url'] . '/' . drupal_get_path('module', 'feeds') . '/tests/feeds/developmentseed.rss2';
    }
    $edit = array(
      'fetcher[source]' => $feed_url,
    );
    if (!$fid) {
      $edit['title'] = $this->randomString();
      $this->drupalPost('feed/add/' . $id, $edit, 'Save');
      $fid = $this->getFid($this->getUrl());
    }
    else {
      $this->drupalPost("feed/$fid/edit", $edit, 'Save');
      $this->feedImportItems($fid);
    }

    // Check whether feed got recorded in feeds_feed table.
    $this->assertEqual(1, db_query("SELECT COUNT(*) FROM {feeds_feed} WHERE importer = :id AND fid = :fid", array(':id' => $id, ':fid' => $fid))->fetchField());
    $feed = db_query("SELECT * FROM {feeds_feed} WHERE importer = :id AND fid = :fid",  array(':id' => $id, ':fid' => $fid))->fetchObject();
    $config = unserialize($feed->config);

    $plugin_id = isset($config['http']) ? 'http' : 'file';

    $this->assertEqual($config[$plugin_id]['source'], $feed_url, t('URL in DB correct.'));

    // Check whether feed got properly added to scheduler.
    $this->assertEqual(1, db_query("SELECT COUNT(*) FROM {job_schedule} WHERE type = :id AND id = :fid AND name = 'feeds_feed_import' AND scheduled = 0", array(':id' => $id, ':fid' => $fid))->fetchField());

    // Check expire scheduler.
    $jobs = db_query("SELECT COUNT(*) FROM {job_schedule} WHERE type = :id AND id = :fid AND name = 'feeds_feed_expire'", array(':id' => $id, ':fid' => $fid))->fetchField();
    if (feeds_importer($id)->processor->expiryTime() == FEEDS_EXPIRE_NEVER) {
      $this->assertEqual(0, $jobs);
    }
    else {
      $this->assertEqual(1, $jobs);
    }

    return $fid;
  }

  /**
   * Import a file through the import form. Assumes FeedsFileFetcher in place.
   */
  public function importFile($id, $file, $fid = NULL, $title = NULL) {

    if (!$title) {
      $title = $this->randomString();
    }

    $this->assertTrue(file_exists($file), 'Source file exists');
    $edit = array(
      'files[fetcher]' => $file,
    );

    if (!$fid) {
      $edit['title'] = $title;
      $this->drupalPost('feed/add/' . $id, $edit, 'Save');
    }
    else {
      $this->drupalPost("feed/$fid/edit", $edit, 'Save');
      $this->feedImportItems($fid);
    }

    return $this->getFid($this->getUrl());
  }

  /**
   * Delete the items belonging to a feed.
   *
   * @param $fid
   *   The fid to delete items for.
   */
  public function feedDeleteItems($fid) {
    $this->drupalPost("feed/$fid/delete-items", array(), 'Delete items');
  }

  /**
   * Delete the items belonging to a feed.
   *
   * @param $fid
   *   The fid to delete items for.
   */
  public function feedImportItems($fid) {
    $this->drupalPost("feed/$fid/import", array(), 'Import');
  }

  /**
   * Deletes a feed.
   *
   * @param $fid
   *   The fid to delete.
   */
  public function feedDelete($fid) {
    $this->drupalPost("feed/$fid/delete", array(), 'Delete');
  }

  /**
   * Assert a feeds configuration's plugins.
   *
   * @deprecated:
   *   Use setPlugin() instead.
   *
   * @todo Refactor users of assertPlugin() and make them use setPugin() instead.
   */
  public function assertPlugins($id, $fetcher, $parser, $processor) {
    // Assert actual configuration.
    $config = config('feeds.importer.' . $id)->get('config');

    $this->assertEqual($config['fetcher']['plugin_key'], $fetcher, 'Correct fetcher');
    $this->assertEqual($config['parser']['plugin_key'], $parser, 'Correct parser');
    $this->assertEqual($config['processor']['plugin_key'], $processor, 'Correct processor');
  }

   /**
    * Adds mappings to a given configuration.
    *
    * @param string $id
    *   ID of the importer.
    * @param array $mappings
    *   An array of mapping arrays. Each mapping array must have a source and
    *   an target key and can have a unique key.
    * @param bool $test_mappings
    *   (optional) TRUE to automatically test mapping configs. Defaults to TRUE.
    */
  public function addMappings($id, $mappings, $test_mappings = TRUE) {

    $path = "admin/structure/feeds/manage/$id/mapping";

    // Iterate through all mappings and add the mapping via the form.
    foreach ($mappings as $i => $mapping) {

      if ($test_mappings) {
        $current_mapping_key = $this->mappingExists($id, $i, $mapping['source'], $mapping['target']);
        $this->assertEqual($current_mapping_key, -1, 'Mapping does not exist before addition.');
      }

      // Get unique flag and unset it. Otherwise, drupalPost will complain that
      // Split up config and mapping.
      $config = $mapping;
      unset($config['source'], $config['target']);
      $mapping = array('source' => $mapping['source'], 'target' => $mapping['target']);

      // Add mapping.
      $this->drupalPost($path, $mapping, t('Save'));

      // If there are other configuration options, set them.
      if ($config) {
        $this->drupalPostAJAX(NULL, array(), 'mapping_settings_edit_' . $i);

        // Set some settings.
        $edit = array();
        foreach ($config as $key => $value) {
          $edit["config[$i][settings][$key]"] = $value;
        }
        $this->drupalPostAJAX(NULL, $edit, 'mapping_settings_update_' . $i);
        $this->drupalPost(NULL, array(), t('Save'));
      }

      if ($test_mappings) {
        $current_mapping_key = $this->mappingExists($id, $i, $mapping['source'], $mapping['target']);
        $this->assertTrue($current_mapping_key >= 0, 'Mapping exists after addition.');
      }
    }
  }

  /**
   * Remove mappings from a given configuration.
   *
   * @param array $mappings
   *   An array of mapping arrays. Each mapping array must have a source and
   *   a target key and can have a unique key.
   * @param bool $test_mappings
   *   (optional) TRUE to automatically test mapping configs. Defaults to TRUE.
   */
  public function removeMappings($id, $mappings, $test_mappings = TRUE) {
    $path = "admin/structure/feeds/manage/$id/mapping";

    $current_mappings = $this->getCurrentMappings($id);

    // Iterate through all mappings and remove via the form.
    foreach ($mappings as $i => $mapping) {

      if ($test_mappings) {
        $current_mapping_key = $this->mappingExists($id, $i, $mapping['source'], $mapping['target']);
        $this->assertEqual($current_mapping_key, $i, 'Mapping exists before removal.');
      }

      $remove_mapping = array("remove_flags[$i]" => 1);

      $this->drupalPost($path, $remove_mapping, t('Save'));

      $this->assertText('Your changes have been saved.');

      if ($test_mappings) {
        $current_mapping_key = $this->mappingExists($id, $i, $mapping['source'], $mapping['target']);
        $this->assertEqual($current_mapping_key, -1, 'Mapping does not exist after removal.');
      }
    }
  }

  /**
   * Gets an array of current mappings from the feeds_importer config.
   *
   * @param string $id
   *   ID of the importer.
   *
   * @return bool|array
   *   FALSE if the importer has no mappings, or an an array of mappings.
   */
  public function getCurrentMappings($id) {
    $config = config('feeds.importer.' . $id)->get('config');

    // We are very specific here. 'mappings' can either be an array or not
    // exist.
    if (array_key_exists('mappings', $config['processor']['config'])) {
      $this->assertTrue(is_array($config['processor']['config']['mappings']), 'Mappings is an array.');

      return $config['processor']['config']['mappings'];
    }

    return FALSE;
  }

  /**
   * Determines if a mapping exists for a given importer.
   *
   * @param string $id
   *   ID of the importer.
   * @param integer $i
   *   The key of the mapping.
   * @param string $source
   *   The source field.
   * @param string $target
   *   The target field.
   *
   * @return integer
   *   -1 if the mapping doesn't exist, the key of the mapping otherwise.
   */
  public function mappingExists($id, $i, $source, $target) {

    $current_mappings = $this->getCurrentMappings($id);

    if ($current_mappings) {
      foreach ($current_mappings as $key => $mapping) {
        if ($mapping['source'] == $source && $mapping['target'] == $target && $key == $i) {
          return $key;
        }
      }
    }

    return -1;
  }

  /**
   * Helper function, retrieves node id from a URL.
   */
  public function getFid($url) {
    $matches = array();
    preg_match('/feed\/(\d+?)$/', $url, $matches);
    $fid = $matches[1];

    // Test for actual integerness.
    $this->assertTrue($fid === (string) (int) $fid, 'Feed id is an integer.');

    return $fid;
  }

  /**
   * Copies a directory.
   */
  public function copyDir($source, $dest) {
    $result = file_prepare_directory($dest, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
    foreach (@scandir($source) as $file) {
      if (is_file("$source/$file")) {
        $file = file_unmanaged_copy("$source/$file", "$dest/$file");
      }
    }
  }

  /**
   * Download and extract SimplePIE.
   *
   * Sets the 'feeds_simplepie_library_dir' variable to the directory where
   * SimplePie is downloaded.
   */
  function downloadExtractSimplePie($version) {
    $url = "http://simplepie.org/downloads/simplepie_$version.mini.php";
    $filename = 'simplepie.mini.php';

    // Avoid downloading the file dozens of times
    $library_dir = DRUPAL_ROOT . '/' . $this->originalFileDirectory . '/simpletest/feeds';
    $simplepie_library_dir = $library_dir . '/simplepie';

    if (!file_exists($library_dir)) {
      drupal_mkdir($library_dir);
    }

    if (!file_exists($simplepie_library_dir)) {
      drupal_mkdir($simplepie_library_dir);
    }

    // Local file name.
    $local_file = $simplepie_library_dir . '/' . $filename;

    // Begin single threaded code.
    if (function_exists('sem_get')) {
      $semaphore = sem_get(ftok(__FILE__, 1));
      sem_acquire($semaphore);
    }

    // Download and extact the archive, but only in one thread.
    if (!file_exists($local_file)) {
      $local_file = system_retrieve_file($url, $local_file, FALSE, FILE_EXISTS_REPLACE);
    }

    if (function_exists('sem_get')) {
      sem_release($semaphore);
    }
    // End single threaded code.

    // Verify that files were successfully extracted.
    $this->assertTrue(file_exists($local_file), t('@file found.', array('@file' => $local_file)));

    // Set the simpletest library directory.
    variable_set('feeds_library_dir', $library_dir);
  }

}
