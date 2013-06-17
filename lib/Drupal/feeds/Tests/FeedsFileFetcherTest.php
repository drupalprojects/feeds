<?php

/**
 * @file
 * File fetcher tests.
 */

namespace Drupal\feeds\Tests;

/**
 * File fetcher test class.
 */
class FeedsFileFetcherTest extends FeedsWebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'File fetcher',
      'description' => 'Tests for file fetcher plugin.',
      'group' => 'Feeds',
    );
  }

  /**
   * Test scheduling on cron.
   */
  public function testPublicFiles() {
    // Set up an importer.
    $this->createImporterConfiguration('Node import', 'node');
    // Set and configure plugins and mappings.
    $this->setPlugin('node', 'fetcher', 'file');
    $this->setPlugin('node', 'parser', 'csv');

    $this->addMappings('node', array(
      0 => array(
        'source' => 'title',
        'target' => 'title',
      ),
    ));
    // Straight up upload is covered in other tests, focus on direct mode
    // and file batching here.
    $this->setSettings('node', 'fetcher', array(
      'direct' => TRUE,
      'directory' => 'public://feeds',
    ));

    // Verify that invalid paths are not accepted.
    foreach (array('/tmp/') as $path) {
      $edit = array('title' => $this->randomString(), 'fetcher[source]' => $path);
      $this->drupalPost('feed/add/node', $edit, 'Save');
      $this->assertText("The file needs to reside within the site's files directory, its path needs to start with scheme://. Available schemes:");
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

    $this->importURL('node', $dir);
    $this->assertText('Created 18 nodes');
    $count = db_query("SELECT COUNT(*) FROM {node}")->fetchField();
    $this->assertEqual($count, 18, t("@count nodes in the database.", array('@count' => $count)));
  }

  /**
   * Test uploading private files.
   */
  public function testPrivateFiles() {
    // Set up an importer.
    $this->createImporterConfiguration('Node import', 'node');
    // Set and configure plugins and mappings.
    $this->setPlugin('node', 'fetcher', 'file');
    $this->setPlugin('node', 'parser', 'csv');
    $this->addMappings('node', array(
      0 => array(
        'source' => 'title',
        'target' => 'title',
      ),
    ));
    // Straight up upload is covered in other tests, focus on direct mode
    // and file batching here.
    $this->setSettings('node', 'fetcher', array(
      'direct' => TRUE,
      'directory' => 'private://feeds',
    ));

    // Verify batching through directories.
    // Copy directory of files.
    $dir = 'private://batchtest';
    $this->copyDir($this->absolutePath() . '/tests/feeds/batch', $dir);

    // Ingest directory of files. Set limit to 5 to force processor to batch,
    // too.
    variable_set('feeds_process_limit', 5);
    $this->importURL('node', $dir);
    $this->assertText('Created 18 nodes');
    $count = db_query("SELECT COUNT(*) FROM {node}")->fetchField();
    $this->assertEqual($count, 18, t("@count nodes in the database.", array('@count' => $count)));
  }

}
