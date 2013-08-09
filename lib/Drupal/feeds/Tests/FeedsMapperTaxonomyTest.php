<?php

/**
 * @file
 * Test case for taxonomy mapper mappers/taxonomy.inc.
 */

namespace Drupal\feeds\Tests;

use Drupal\feeds\FeedsMapperTestBase;
use Drupal\feeds\Plugin\feeds\Target\Taxonomy;

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

  public function setUp() {
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

    $this->createField('article', 'tags', 'taxonomy_term_reference', $field_settings);

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

    $this->reuseFeedField('syndication', 'tags', 'taxonomy_term_reference');
  }

  /**
   * Tests inheriting taxonomy from the feed node.
   */
  public function testInheritTaxonomy() {

    // Adjust importer settings
    $this->setSettings('syndication', NULL, array('import_period' => FEEDS_SCHEDULE_NEVER));
    $this->setSettings('syndication', NULL, array('import_on_create' => FALSE));
    $this->assertText('Do not import on submission');

    // Map feed node's taxonomy to feed item node's taxonomy.
    $this->addMappings('syndication', array(
      5 => array(
        'source' => 'parent:taxonomy:field_tags',
        'target' => 'field_tags:target_id',
      ),
    ));

    // Create feed node and add term term1.
    $fid = $this->createFeed('syndication', NULL, 'Syndication');

    $this->drupalPost("feed/$fid/edit", array('field_tags[und][]' => 1), t('Save'));

    // Import nodes.
    $this->feedImportItems($fid);
    $this->assertText('Created 10 nodes.');

    $count = db_query("SELECT COUNT(*) FROM {taxonomy_index}")->fetchField();

    // There should be one term for each node imported.
    $this->assertEqual(10, $count, t('Found @count tags for all feed nodes and feed items.', array('@count' => $count)));
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

    $this->setSettings('syndication', 'processor', array(
      'skip_hash_check' => TRUE,
      'update_existing' => 2,
    ));
    $mappings = array(
      5 => array(
        'source' => 'tags',
        'target' => 'field_tags:target_id',
        'term_search' => 0,
      ),
    );
    $this->addMappings('syndication', $mappings);
    $fid = $this->createFeed('syndication', NULL, 'Syndication');
    $this->assertText('Created 10 nodes.');
    // Check that terms we not auto-created.
    $this->drupalGet('node/1');
    foreach ($terms as $term) {
      $this->assertNoTaxonomyTerm($term);
    }
    $this->drupalGet('node/2');
    $this->assertNoTaxonomyTerm('Washington DC');

    // Change the mapping configuration.
    $this->removeMappings('syndication', $mappings);
    // Turn on autocreate.
    $mappings[5]['autocreate'] = TRUE;
    $this->addMappings('syndication', $mappings);
    $this->feedImportItems($fid);
    $this->assertText('Updated 10 nodes.');

    $this->drupalGet('node/1');
    foreach ($terms as $term) {
      $this->assertTaxonomyTerm($term);
    }
    $this->drupalGet('node/2');
    $this->assertTaxonomyTerm('Washington DC');

    $count = db_query('SELECT COUNT(name) FROM {taxonomy_term_data}')->fetchField();
    $this->assertEqual($count, 31, t('Found @count of terms in the database.', array('@count' => $count)));

    // Run import again. This verifys that the terms we found by name.
    $this->feedImportItems($fid);
    $this->assertText('Updated 10 nodes.');
    $count = db_query('SELECT COUNT(name) FROM {taxonomy_term_data}')->fetchField();
    $this->assertEqual($count, 31, t('Found @count of terms in the database.', array('@count' => $count)));
  }

  /**
   * Tests mapping to taxonomy terms by tid.
   */
  public function testSearchByID() {
    drupal_flush_all_caches();
    // Create 10 terms. The first one was created in setup.
    $tids = array(array('target_id' => 1));
    foreach (range(2, 10) as $i) {
      $term = entity_create('taxonomy_term', array(
        'name' => 'term' . $i,
        'vid' => 'tags',
      ));
      $term->save();
      $tids[] = array('target_id' => $term->id());
    }

    $target = 'field_tags';
    $entity = entity_create('node', array('type' => 'article'))->getBCEntity();
    $mapper = new Taxonomy(array(), 'test', array());
    $mapping = array(
      'term_search' => 1,
    );

    $feed = entity_create('feeds_feed', array('importer' => 'syndication'));
    $mapper->setTarget($feed, $entity, $target, $tids, $mapping);
    $this->assertEqual(count($entity->field_tags['und']), 10);

    // Test a second mapping with a bogus term id.
    $mapper->setTarget($feed, $entity, $target, array(array('target_id' => 1234)), $mapping);
    $this->assertEqual(count($entity->field_tags['und']), 10);
  }

  /**
   * Tests mapping to a taxonomy term's guid.
   */
  public function testSearchByGUID() {
    drupal_flush_all_caches();

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
      $guids[] = array('target_id' => $guid);
      $record = array(
        'entity_type' => 'taxonomy_term',
        'entity_id' => $tid,
        'fid' => 1,
        'imported' => REQUEST_TIME,
        'url' => '',
        'guid' => $guid,
      );
      drupal_write_record('feeds_item', $record);
    }

    $entity = entity_create('node', array('type' => 'article'))->getBCEntity();

    $target = 'field_tags';

    $feed = entity_create('feeds_feed', array('importer' => 'syndication'));
    $mapper = new Taxonomy(array(), 'test', array());

    $mapping = array(
      'term_search' => 2,
    );

    $mapper->setTarget($feed, $entity, $target, $guids, $mapping);
    $this->assertEqual(count($entity->field_tags['und']), 10);
    foreach ($entity->field_tags['und'] as $delta => $values) {
      $this->assertEqual($tids[$delta], $values['target_id'], 'Correct term id foud.');
    }

    // Test a second mapping with a bogus term id.
    $mapper->setTarget($feed, $entity, $target, array(array('value' => 1234)), $mapping);
    $this->assertEqual(count($entity->field_tags['und']), 10);
    foreach ($entity->field_tags['und'] as $delta => $values) {
      $this->assertEqual($tids[$delta], $values['target_id'], 'Correct term id foud.');
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
