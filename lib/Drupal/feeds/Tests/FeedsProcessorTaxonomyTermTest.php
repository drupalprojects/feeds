<?php

/**
 * @file
 * Tests for plugins/FeedsTermProcessor.inc
 */

namespace Drupal\feeds\Tests;

use Drupal\feeds\FeedsWebTestBase;

/**
 * Test aggregating a feed as data records.
 */
class FeedsProcessorTaxonomyTermTest extends FeedsWebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'CSV import to taxonomy',
      'description' => 'Tests a standalone import configuration that uses file fetcher and CSV parser to import taxonomy terms from a CSV file.',
      'group' => 'Feeds',
    );
  }

  /**
   * Set up test.
   */
  public function setUp() {
    parent::setUp();

    // Create an importer.
    $this->createImporterConfiguration('Term import', 'term_import');

    // Set and configure plugins and mappings.
    $this->setPlugin('term_import', 'fetcher', 'upload');
    $this->setPlugin('term_import', 'parser', 'csv');
    $this->setPlugin('term_import', 'processor', 'entity:taxonomy_term');

    // Create vocabulary.
    entity_create('taxonomy_vocabulary', array(
      'name' => 'Addams vocabulary',
      'vid' => 'addams',
    ))->save();

    $this->setSettings('term_import', 'processor', array('values[vid]' => 'addams'));
  }

  /**
   * Test term creation, refreshing/deleting feeds and feed items.
   */
  public function test() {

    $this->addMappings('term_import', array(
      0 => array(
        'source' => 'name',
        'target' => 'name',
        'unique' => 1,
      ),
    ));

    // Import and assert.
    $fid = $this->importFile('term_import', $this->absolutePath() . '/tests/feeds/users.csv');
    $this->assertText('Created 5 terms');
    $this->drupalGet('admin/structure/taxonomy/manage/addams');
    $this->assertText('Morticia');
    $this->assertText('Fester');
    $this->assertText('Gomez');
    $this->assertText('Pugsley');

    // Import again.
    $this->importFile('term_import', $this->absolutePath() . '/tests/feeds/users.csv', $fid);
    $this->assertText('There are no new terms.');

    // Force update.
    $this->setSettings('term_import', 'processor', array(
      'skip_hash_check' => TRUE,
      'update_existing' => 2,
    ));
    $this->importFile('term_import', $this->absolutePath() . '/tests/feeds/users.csv', $fid);
    $this->assertText('Updated 5 terms.');

    // Add a term manually, delete all terms, this term should still stand.
    entity_create('taxonomy_term', array(
      'name' => 'Cousin Itt',
      'vid' => 'addams',
    ))->save();

    $this->feedDeleteItems($fid);
    $this->drupalGet('admin/structure/taxonomy/manage/addams');
    $this->assertText('Cousin Itt');
    $this->assertNoText('Morticia');
    $this->assertNoText('Fester');
    $this->assertNoText('Gomez');
    $this->assertNoText('Pugsley');
  }

}
