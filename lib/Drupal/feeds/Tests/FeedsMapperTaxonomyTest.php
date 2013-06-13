<?php

/**
 * @file
 * Test case for taxonomy mapper mappers/taxonomy.inc.
 */

namespace Drupal\feeds\Tests;

use Drupal\feeds\Plugin\FeedsPlugin;
use Drupal\Core\Language\Language;

/**
 * Class for testing Feeds <em>content</em> mapper.
 */
class FeedsMapperTaxonomyTest extends FeedsMapperTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Mapper: Taxonomy',
      'description' => 'Test Feeds Mapper support for Taxonomy.',
      'group' => 'Feeds',
    );
  }

  function setUp() {
    parent::setUp();

    // Add Tags vocabulary.
    entity_create('taxonomy_vocabulary', array(
      'name' => 'Tags',
      'vid' => 'tags',
    ))->save();
    entity_create('taxonomy_term', array(
      'name' => 'term1',
      'vid' => 'tags',
    ))->save();

    $field_settings = array(
      'field[cardinality]' => -1,
      'field[settings][allowed_values][0][vocabulary]' => 'tags',
    );

    $this->createField('article', 'tags', 'taxonomy_term_reference', 'options_select', $field_settings);
    $this->reuseField('page', 'tags', 'taxonomy_term_reference');

    $edit = array(
      'fields[field_tags][type]' => 'taxonomy_term_reference_link',
    );
    $this->drupalPost('admin/structure/types/manage/article/display', $edit, t('Save'));

    // Create an importer configuration with basic mapping.
    $this->createImporterConfiguration('Syndication', 'syndication');
    $this->addMappings('syndication', array(
      0 => array(
        'source' => 'title',
        'target' => 'title',
      ),
      1 => array(
        'source' => 'description',
        'target' => 'body',
      ),
      2 => array(
        'source' => 'timestamp',
        'target' => 'created',
      ),
      3 => array(
        'source' => 'url',
        'target' => 'url',
        'unique' => TRUE,
      ),
      4 => array(
        'source' => 'guid',
        'target' => 'guid',
        'unique' => TRUE,
      ),
    ));
  }

  /**
   * Tests inheriting taxonomy from the feed node.
   */
  function testInheritTaxonomy() {

    // Adjust importer settings
    $this->setSettings('syndication', NULL, array('import_period' => FEEDS_SCHEDULE_NEVER));
    $this->setSettings('syndication', NULL, array('import_on_create' => FALSE));
    $this->assertText('Do not import on submission');

    // Map feed node's taxonomy to feed item node's taxonomy.
    $this->addMappings('syndication', array(
      5 => array(
        'source' => 'parent:taxonomy:field_tags',
        'target' => 'field_tags',
      ),
    ));

    // Create feed node and add term term1.
    $langcode = Language::LANGCODE_NOT_SPECIFIED;
    $nid = $this->createFeedNode('syndication', NULL, 'Syndication');
    $term = taxonomy_term_load_multiple_by_name('term1');
    $term = reset($term);
    $node = node_load($nid)->getNGEntity();
    $node->field_tags = $term->id();
    $node->save();

    // Import nodes.
    $this->drupalPost("node/$nid/import", array(), 'Import');
    $this->assertText('Created 10 nodes.');

    $count = db_query("SELECT COUNT(*) FROM {taxonomy_index}")->fetchField();

    // There should be one term for each node imported plus the term on the feed node.
    $this->assertEqual(11, $count, 'Found correct number of tags for all feed nodes and feed items.');
  }

  /**
   * Tests searching taxonomy terms by name.
   */
  public function testSearchByName() {

    $terms = array(
      'Drupal',
      'localization',
      'localization client',
      'localization server',
      'open atrium',
      'translation',
      'translation server',
      'Drupal planet',
    );

    $this->setSettings('syndication', 'node', array(
      'skip_hash_check' => TRUE,
      'update_existing' => 2,
    ));
    $mappings = array(
      5 => array(
        'source' => 'tags',
        'target' => 'field_tags',
        'term_search' => 0,
      ),
    );
    $this->addMappings('syndication', $mappings);
    $nid = $this->createFeedNode('syndication', NULL, 'Syndication');
    $this->assertText('Created 10 nodes.');
    // Check that terms we not auto-created.
    $this->drupalGet('node/2');
    foreach ($terms as $term) {
      $this->assertNoTaxonomyTerm($term);
    }
    $this->drupalGet('node/3');
    $this->assertNoTaxonomyTerm('Washington DC');

    // Change the mapping configuration.
    $this->removeMappings('syndication', $mappings);
    // Turn on autocreate.
    $mappings[5]['autocreate'] = TRUE;
    $this->addMappings('syndication', $mappings);
    $this->drupalPost('node/' . $nid . '/import', array(), t('Import'));
    $this->assertText('Updated 10 nodes.');

    $this->drupalGet('node/2');
    foreach ($terms as $term) {
      $this->assertTaxonomyTerm($term);
    }
    $this->drupalGet('node/3');
    $this->assertTaxonomyTerm('Washington DC');

    $names = db_query('SELECT name FROM {taxonomy_term_data}')->fetchCol();
    $this->assertEqual(count($names), 31, 'Found correct number of terms in the database.');

    // Run import again. This verifys that the terms we found by name.
    $this->drupalPost('node/' . $nid . '/import', array(), t('Import'));
    $this->assertText('Updated 10 nodes.');
    $names = db_query('SELECT name FROM {taxonomy_term_data}')->fetchCol();
    $this->assertEqual(count($names), 31, 'Found correct number of terms in the database.');
  }

  /**
   * Tests mapping to taxonomy terms by tid.
   */
  public function testSearchByID() {
    // Create 10 terms. The first one was created in setup.
    $tids = array(1);
    foreach (range(2, 10) as $i) {
      $term = entity_create('taxonomy_term', array(
        'name' => 'term' . $i,
        'vid' => 'tags',
      ));
      $term->save();
      $tids[] = $term->id();
    }

    FeedsPlugin::loadMappers();

    $target = 'field_tags';
    $mapping = array(
      'term_search' => FEEDS_TAXONOMY_SEARCH_TERM_ID,
    );
    $entity = entity_create('node', array('type' => 'article'))->getBCEntity();

    taxonomy_feeds_set_target(NULL, $entity, $target, $tids, $mapping);
    $this->assertEqual(count($entity->field_tags['und']), 10);

    // Test a second mapping with a bogus term id.
    taxonomy_feeds_set_target(NULL, $entity, $target, array(1234), $mapping);
    $this->assertEqual(count($entity->field_tags['und']), 10);
  }

  /**
   * Tests mapping to a taxonomy term's guid.
   */
  public function testSearchByGUID() {
    // Create 10 terms. The first one was created in setup.
    $tids = array(1);
    foreach (range(2, 10) as $i) {
      $term = entity_create('taxonomy_term', array(
        'name' => 'term' . $i,
        'vid' => 'tags',
      ));
      $term->save();
      $tids[] = $term->id();
    }

    // Create a bunch of bogus imported terms.
    $guids = array();
    foreach ($tids as $tid) {
      $guid = 100 * $tid;
      $guids[] = $guid;
      $record = array(
        'entity_type' => 'taxonomy_term',
        'entity_id' => $tid,
        'id' => 'does_not_exist',
        'feed_nid' => 0,
        'imported' => REQUEST_TIME,
        'url' => '',
        'guid' => $guid,
      );
      drupal_write_record('feeds_item', $record);
    }

    FeedsPlugin::loadMappers();

    $entity = entity_create('node', array('type' => 'article'))->getBCEntity();

    $target = 'field_tags';
    $mapping = array(
      'term_search' => FEEDS_TAXONOMY_SEARCH_TERM_GUID,
    );

    taxonomy_feeds_set_target(NULL, $entity, $target, $guids, $mapping);
    $this->assertEqual(count($entity->field_tags['und']), 10);
    foreach ($entity->field_tags['und'] as $delta => $values) {
      $this->assertEqual($tids[$delta], $values['tid'], 'Correct term id foud.');
    }

    // Test a second mapping with a bogus term id.
    taxonomy_feeds_set_target(NULL, $entity, $target, array(1234), $mapping);
    $this->assertEqual(count($entity->field_tags['und']), 10);
    foreach ($entity->field_tags['und'] as $delta => $values) {
      $this->assertEqual($tids[$delta], $values['tid'], 'Correct term id foud.');
    }
  }

  /**
   * Finds node style taxonomy term markup in DOM.
   */
  public function assertTaxonomyTerm($term) {
    $term = check_plain($term);
    $this->assertPattern('/<a href="\/.*taxonomy\/term\/[0-9]+">' . $term . '<\/a>/', 'Found ' . $term);
  }

  /**
   * Asserts that the term does not exist on a node page.
   */
  public function assertNoTaxonomyTerm($term) {
    $term = check_plain($term);
    $this->assertNoPattern('/<a href="\/.*taxonomy\/term\/[0-9]+">' . $term . '<\/a>/', 'Did not find ' . $term);
  }
}
