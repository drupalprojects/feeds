<?php

/**
 * @file
 * Test case for Filefield mapper mappers/filefield.inc.
 */

namespace Drupal\feeds\Tests;

use Drupal\feeds\FeedsEnclosure;
use Drupal\feeds\FeedsMapperTestBase;

/**
 * Class for testing Feeds file mapper.
 */
class FeedsMapperFileTest extends FeedsMapperTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'feeds_tests',
  );

  public static function getInfo() {
    return array(
      'name' => 'Mapper: File',
      'description' => 'Test Feeds Mapper support for file fields.',
      'group' => 'Feeds',
    );
  }

  /**
   * Basic test loading a single entry CSV file.
   */
  public function test() {
    $typename = $this->createContentType(array(), array('files' => 'file'));

    // 1) Test mapping remote resources to file field.

    // Create importer configuration.
    $this->createImporterConfiguration();
    $this->setSettings('syndication', 'processor', array('bundle' => $typename));
    $this->addMappings('syndication', array(
      0 => array(
        'source' => 'title',
        'target' => 'title',
      ),
      1 => array(
        'source' => 'timestamp',
        'target' => 'created',
      ),
      2 => array(
        'source' => 'enclosures',
        'target' => 'field_files:uri',
      ),
      3 => array(
        'source' => 'guid',
        'target' => 'guid',
        'unique' => TRUE,
      ),
    ));
    $fid = $this->createFeed('syndication', $this->getAbsoluteUrl('testing/feeds/flickr.xml'));
    $this->assertText('Created 5 nodes');

    $files = $this->listTestFiles();
    $entities = db_select('feeds_item')
      ->fields('feeds_item', array('entity_id'))
      ->condition('fid', $fid)
      ->execute()
      ->fetchCol();

    $this->assertEqual(count($entities), 5, t('@count nodes in the database.', array('@count' => count($entities))));

    foreach ($entities as $entity_id) {
      $this->drupalGet('node/' . $entity_id . '/edit');
      $f = new FeedsEnclosure(array_shift($files), NULL);
      $this->assertText($f->getLocalValue());
    }

    // 2) Test mapping local resources to file field.

    // Copy directory of files, CSV file expects them in public://images, point
    // file field to a 'resources' directory. Feeds should copy files from
    // images/ to resources/ on import.
    $this->copyDir($this->absolutePath() . '/tests/feeds/assets', 'public://images');
    $edit = array(
      'instance[settings][file_directory]' => 'resources',
    );
    $this->drupalPost("admin/structure/types/manage/$typename/fields/node.$typename.field_files", $edit, t('Save settings'));

    // Create a CSV importer configuration.
    $this->createImporterConfiguration('Node import from CSV', 'node');
    $this->setPlugin('node', 'parser', 'csv');
    $this->setSettings('node', 'processor', array('bundle' => $typename));
    $this->addMappings('node', array(
      0 => array(
        'source' => 'title',
        'target' => 'title',
      ),
      1 => array(
        'source' => 'file',
        'target' => 'field_files:uri',
      ),
    ));

    // Import.
    $fid = $this->importURL('node', $this->getAbsoluteUrl('testing/feeds/files.csv'));
    $this->assertText('Created 5 nodes');

    // Assert: files should be in resources/.
    $files = $this->listTestFiles();
    $entities = db_select('feeds_item')
      ->fields('feeds_item', array('entity_id'))
      ->condition('fid', $fid)
      ->execute();

    foreach ($entities as $entity) {
      $this->drupalGet('node/' . $entity->entity_id . '/edit');
      $f = new FeedsEnclosure(array_shift($files), NULL);
      $this->assertRaw('resources/' . $f->getUrlEncodedValue());
    }

    // 3) Test mapping of local resources, this time leave files in place.
    $this->feedDeleteItems($fid);
    // Setting the fields file directory to images will make copying files
    // obsolete.
    $edit = array(
      'instance[settings][file_directory]' => 'images',
    );
    $this->drupalPost("admin/structure/types/manage/$typename/fields/node.$typename.field_files", $edit, t('Save settings'));
    $this->importURL('node', $this->getAbsoluteUrl('testing/feeds/files.csv'), $fid);
    $this->assertText('Created 5 nodes');

    // Assert: files should be in images/ now.
    $files = $this->listTestFiles();
    $entities = db_select('feeds_item')
      ->fields('feeds_item', array('entity_id'))
      ->condition('fid', $fid)
      ->execute();

    foreach ($entities as $entity) {
      $this->drupalGet('node/' . $entity->entity_id . '/edit');
      $f = new FeedsEnclosure(array_shift($files), NULL);
      $this->assertRaw('images/' . $f->getUrlEncodedValue());
      $this->assertTrue(is_file('public://images/' . $f->getValue()));
    }

    // Deleting all imported items will delete the files from the images/ dir.
    $this->feedDeleteItems($fid);

    // @todo Figure out why these files are not being removed.
    // $this->cronRun();
    // $this->cronRun();

    // foreach ($this->listTestFiles() as $file) {
    //   $this->assertFalse(is_file("public://images/$file"));
    // }
  }

  /**
   * Tests mapping to an image field.
   */
  public function testImages() {
    $typename = $this->createContentType(array(), array('images' => 'image'));

    // Enable title and alt mapping.
    $edit = array(
      'instance[settings][alt_field]' => 1,
      'instance[settings][title_field]' => 1,
    );
    $this->drupalPost("admin/structure/types/manage/$typename/fields/node.$typename.field_images", $edit, t('Save settings'));

    // Create a CSV importer configuration.
    $this->createImporterConfiguration('Node import from CSV', 'image_test');
    $this->setPlugin('image_test', 'parser', 'csv');
    $this->setSettings('image_test', 'processor', array('bundle' => $typename));
    $this->addMappings('image_test', array(
      0 => array(
        'source' => 'title',
        'target' => 'title',
      ),
      1 => array(
        'source' => 'file',
        'target' => 'field_images:uri',
      ),
      2 => array(
        'source' => 'title2',
        'target' => 'field_images:title',
      ),
      3 => array(
        'source' => 'alt',
        'target' => 'field_images:alt',
      ),
    ));

    // Import.
    $fid = $this->importURL('image_test', $this->getAbsoluteUrl('testing/feeds/files-remote.csv'));
    $this->assertText('Created 5 nodes');

    // Assert files exist.
    $files = $this->listTestFiles();
    $entities = db_select('feeds_item')
      ->fields('feeds_item', array('entity_id'))
      ->condition('fid', $fid)
      ->execute();

    foreach ($entities as $i => $entity) {
      $this->drupalGet('node/' . $entity->entity_id . '/edit');
      $f = new FeedsEnclosure(array_shift($files), NULL);
      $this->assertRaw($f->getUrlEncodedValue());
      $this->assertRaw("Alt text $i");
      $this->assertRaw("Title text $i");
    }
  }

  /**
   * Lists test files.
   */
  protected function listTestFiles() {
    return array(
      'tubing.jpeg',
      'foosball.jpeg',
      'attersee.jpeg',
      'hstreet.jpeg',
      'la fayette.jpeg',
    );
  }

}
