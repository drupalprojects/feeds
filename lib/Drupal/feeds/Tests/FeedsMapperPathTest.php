<?php

/**
 * @file
 * Test case for path alias mapper path.inc.
 */

namespace Drupal\feeds\Tests;

use Drupal\feeds\FeedsMapperTestBase;

/**
 * Class for testing Feeds <em>path</em> mapper.
 */
class FeedsMapperPathTest extends FeedsMapperTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'field',
    'field_ui',
    'path',
    'job_scheduler',
  );

  public static function getInfo() {
    return array(
      'name' => 'Mapper: Path',
      'description' => 'Test Feeds Mapper support for path aliases.',
      'group' => 'Feeds',
    );
  }

  /**
   * Basic test loading a single entry CSV file.
   */
  public function testNodeAlias() {

    // Create importer configuration.
    $this->createImporterConfiguration($this->randomName(), 'path_test');
    $this->setPlugin('path_test', 'fetcher', 'file');
    $this->setPlugin('path_test', 'parser', 'csv');
    $this->addMappings('path_test', array(
      0 => array(
        'source' => 'Title',
        'target' => 'title',
      ),
      1 => array(
        'source' => 'path',
        'target' => 'path_alias',
      ),
      2 => array(
        'source' => 'GUID',
        'target' => 'guid',
        'unique' => TRUE,
      ),
    ));

    // Turn on update existing.
    $this->setSettings('path_test', 'processor', array('update_existing' => 2));

    // Import CSV file.
    $fid = $this->importFile('path_test', $this->absolutePath() . '/tests/feeds/path_alias.csv');
    $this->assertText('Created 9 nodes');

    $aliases = array();

    for ($i = 1; $i <= 9; $i++) {
      $aliases[] = "path$i";
    }

    $this->assertAliasCount($aliases);

    // Adding a mapping will force update.
    $this->addMappings('path_test', array(
      3 => array(
        'source' => 'fake',
        'target' => 'body',
      ),
    ));
    // Import CSV file.
    $this->importFile('path_test', $this->absolutePath() . '/tests/feeds/path_alias.csv', $fid);
    $this->assertText('Updated 9 nodes');

    // Check that duplicate aliases are not created.
    $this->assertAliasCount($aliases);
  }

  /**
   * Test support for term aliases.
   */
  // public function testTermAlias() {

  //   // Create importer configuration.
  //   $this->createImporterConfiguration($this->randomName(), 'path_test');
  //   $this->setPlugin('path_test', 'fetcher', 'file');
  //   $this->setPlugin('path_test', 'parser', 'csv');
  //   $this->setPlugin('path_test', 'processor', 'taxonomy_term');

  //   // Create vocabulary.
  //   $edit = array(
  //     'name' => 'Addams vocabulary',
  //     'machine_name' => 'addams',
  //   );
  //   $this->drupalPost('admin/structure/taxonomy/add', $edit, t('Save'));

  //   $this->setSettings('path_test', 'processor', array('bundle' => 'addams', 'update_existing' => 2));

  //   // Add mappings.
  //   $this->addMappings('path_test', array(
  //     0 => array(
  //       'source' => 'Title',
  //       'target' => 'name',
  //     ),
  //     1 => array(
  //       'source' => 'path',
  //       'target' => 'path_alias',
  //     ),
  //     2 => array(
  //       'source' => 'GUID',
  //       'target' => 'guid',
  //       'unique' => TRUE,
  //     ),
  //   ));

  //   // Import RSS file.
  //   $this->importFile('path_test', $this->absolutePath() . '/tests/feeds/path_alias.csv');
  //   $this->assertText('Created 9 terms');

  //   $aliases = array();

  //   for ($i = 1; $i <= 9; $i++) {
  //     $aliases[] = "path$i";
  //   }

  //   $this->assertAliasCount($aliases);

  //   // Adding a mapping will force update.
  //   $this->addMappings('path_test', array(
  //     3 => array(
  //       'source' => 'fake',
  //       'target' => 'description',
  //     ),
  //   ));
  //   // Import RSS file.
  //   $this->importFile('path_test', $this->absolutePath() . '/tests/feeds/path_alias.csv');
  //   $this->assertText('Updated 9 terms');

  //   // Check that duplicate aliases are not created.
  //   $this->assertAliasCount($aliases);
  // }

  public function assertAliasCount($aliases) {
    $in_db = db_select('url_alias', 'a')
      ->fields('a')
      ->condition('a.alias', $aliases)
      ->execute()
      ->fetchAll();

    $this->assertEqual(count($in_db), count($aliases), 'Correct number of aliases in db.');
  }
}

/**
 * Class for testing Feeds <em>path</em> mapper with pathauto.module.
 */
// class FeedsMapperPathPathautoTestCase extends FeedsMapperTestCase {
//   public static function getInfo() {
//     return array(
//       'name' => 'Mapper: Path with pathauto',
//       'description' => 'Test Feeds Mapper support for path aliases and pathauto.',
//       'group' => 'Feeds',
//       'dependencies' => array('pathauto'),
//     );
//   }

//   public function setUp() {
//     parent::setUp(array('pathauto'));
//   }

//   /**
//    * Basic for allowing pathauto to override the alias.
//    */
//   public function test() {

//     // Create importer configuration.
//     $this->createImporterConfiguration($this->randomName(), 'path_test');
//     $this->setPlugin('path_test', 'fetcher', 'file');
//     $this->setPlugin('path_test', 'parser', 'csv');
//     $this->addMappings('path_test', array(
//       0 => array(
//         'source' => 'Title',
//         'target' => 'title',
//         'unique' => FALSE,
//       ),
//       1 => array(
//         'source' => 'does_not_exist',
//         'target' => 'path_alias',
//         'pathauto_override' => TRUE,
//       ),
//       2 => array(
//         'source' => 'GUID',
//         'target' => 'guid',
//         'unique' => TRUE,
//       ),
//     ));

//     // Turn on update existing.
//     $this->setSettings('path_test', 'processor', array('update_existing' => 2));

//     // Import RSS file.
//     $this->importFile('path_test', $this->absolutePath() . '/tests/feeds/path_alias.csv');
//     $this->assertText('Created 9 nodes');

//     $aliases = array();

//     for ($i = 1; $i <= 9; $i++) {
//       $aliases[] = "content/pathauto$i";
//     }

//     $this->assertAliasCount($aliases);

//     // Adding a mapping will force update.
//     $this->addMappings('path_test', array(
//       3 => array(
//         'source' => 'fake',
//         'target' => 'body',
//       ),
//     ));
//     // Import RSS file.
//     $this->importFile('path_test', $this->absolutePath() . '/tests/feeds/path_alias.csv');
//     $this->assertText('Updated 9 nodes');

//     // Check that duplicate aliases are not created.
//     $this->assertAliasCount($aliases);
//   }

//   public function assertAliasCount($aliases) {
//     $in_db = db_query("SELECT * FROM {url_alias} WHERE alias IN (:aliases)", array(':aliases' => $aliases))->fetchAll();
//     $this->assertEqual(count($in_db), count($aliases), 'Correct number of aliases in db.');
//   }
// }
