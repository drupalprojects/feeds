<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\DirectoryFetcherTest.
 */

namespace Drupal\feeds\Tests;

use Drupal\feeds\FeedsWebTestBase;

/**
 * Directory fetcher test class.
 */
class DirectoryFetcherTest extends FeedsWebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Directory fetcher',
      'description' => 'Tests for directory fetcher plugin.',
      'group' => 'Feeds',
    );
  }

  /**
   * Tests public file importing.
   */
  public function testPublicFiles() {
    // Set up an importer.
    $this->createImporterConfiguration('Node import', 'node');
    // Set and configure plugins and mappings.
    $this->setPlugin('node', 'fetcher', 'directory');
    $this->setPlugin('node', 'parser', 'csv');

    $this->addMappings('node', array(
      0 => array(
        'source' => 'title',
        'target' => 'title',
      ),
    ));
    $this->setSettings('node', 'fetcher', array(
      'allowed_schemes[private]' => FALSE,
    ));

    // Verify that invalid paths are not accepted.
    foreach (array('/tmp/', 'private://asdfasfd') as $path) {
      $edit = array('title' => $this->randomString(), 'fetcher[source]' => $path);
      $this->drupalPost('feed/add/node', $edit, 'Save');
      $this->assertText("The file needs to reside within the site's files directory, its path needs to start with scheme://. Available schemes:");
      $count = db_query("SELECT COUNT(*) FROM {feeds_feed}")->fetchField();
      $this->assertEqual($count, 0);
    }

    // Verify that invalid files are not accepted.
    foreach (array('public://asdfasfd') as $path) {
      $edit = array('title' => $this->randomString(), 'fetcher[source]' => $path);
      $this->drupalPost('feed/add/node', $edit, 'Save');
      $this->assertText('The specified file or directory does not exist.');
      $count = db_query("SELECT COUNT(*) FROM {feeds_feed}")->fetchField();
      $this->assertEqual($count, 0);
    }

    // Verify batching through directories.
    // Copy directory of files.
    $dir = 'public://batchtest';
    $this->copyDir($this->absolutePath() . '/tests/feeds/batch', $dir);

    // Ingest directory of files. Set limit to 5 to force processor to batch,
    // too.
    variable_set('feeds_process_limit', 5);

    $this->importURL('node', $dir, NULL, 'directory');
    $this->assertText('Created 18 nodes');
    $count = db_query("SELECT COUNT(*) FROM {node}")->fetchField();
    $this->assertEqual($count, 18, t("@count nodes in the database.", array('@count' => $count)));
  }

  /**
   * Tests uploading private files.
   */
  public function testPrivateFiles() {
    // Set up an importer.
    $this->createImporterConfiguration('Node import', 'node');
    // Set and configure plugins and mappings.
    $this->setPlugin('node', 'fetcher', 'directory');
    $this->setPlugin('node', 'parser', 'csv');
    $this->addMappings('node', array(
      0 => array(
        'source' => 'title',
        'target' => 'title',
      ),
    ));

    $this->setSettings('node', 'fetcher', array(
      'allowed_schemes[public]' => FALSE,
    ));

    // Verify that invalid paths are not accepted.
    foreach (array('/tmp/', 'public://asdfasfd') as $path) {
      $edit = array('title' => $this->randomString(), 'fetcher[source]' => $path);
      $this->drupalPost('feed/add/node', $edit, 'Save');
      $this->assertText("The file needs to reside within the site's files directory, its path needs to start with scheme://. Available schemes:");
      $count = db_query("SELECT COUNT(*) FROM {feeds_feed}")->fetchField();
      $this->assertEqual($count, 0);
    }

    // Verify that invalid files are not accepted.
    foreach (array('private://asdfasfd') as $path) {
      $edit = array('title' => $this->randomString(), 'fetcher[source]' => $path);
      $this->drupalPost('feed/add/node', $edit, 'Save');
      $this->assertText('The specified file or directory does not exist.');
      $count = db_query("SELECT COUNT(*) FROM {feeds_feed}")->fetchField();
      $this->assertEqual($count, 0);
    }

    // Verify batching through directories.
    // Copy directory of files.
    $dir = 'private://batchtest';
    $this->copyDir($this->absolutePath() . '/tests/feeds/batch', $dir);

    // Ingest directory of files. Set limit to 5 to force processor to batch,
    // too.
    variable_set('feeds_process_limit', 5);
    $this->importURL('node', $dir, NULL, 'directory');
    $this->assertText('Created 18 nodes');
    $count = db_query("SELECT COUNT(*) FROM {node}")->fetchField();
    $this->assertEqual($count, 18, t("@count nodes in the database.", array('@count' => $count)));
  }

}
