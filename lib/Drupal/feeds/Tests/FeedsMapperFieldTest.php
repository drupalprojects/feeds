<?php

/**
 * @file
 * Test case for simple CCK field mapper mappers/content.inc.
 */

namespace Drupal\feeds\Tests;

use Drupal\feeds\FeedsMapperTestBase;

/**
 * Class for testing Feeds field mapper.
 */
class FeedsMapperFieldTest extends FeedsMapperTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'field',
    'field_ui',
    'number',
    'job_scheduler',
  );

  public static function getInfo() {
    return array(
      'name' => 'Mapper: Fields',
      'description' => 'Test Feeds Mapper support for fields.',
      'group' => 'Feeds',
    );
  }

  /**
   * Basic test loading a double entry CSV file.
   */
  function test() {
    // Create content type.
    $typename = $this->createContentType(array(), array(
      'alpha' => 'text',
      'beta' => 'number_integer',
      'gamma' => 'number_decimal',
      'delta' => 'number_float',
    ));

    // Create and configure importer.
    $this->createImporterConfiguration('Content CSV', 'csv');
    $this->setSettings('csv', NULL, array('import_period' => FEEDS_SCHEDULE_NEVER));
    $this->setPlugin('csv', 'fetcher', 'file');
    $this->setPlugin('csv', 'parser', 'csv');
    $this->setSettings('csv', 'processor', array('values[type]' => $typename));
    $this->addMappings('csv', array(
      0 => array(
        'source' => 'title',
        'target' => 'title',
      ),
      1 => array(
        'source' => 'created',
        'target' => 'created',
      ),
      2 => array(
        'source' => 'body',
        'target' => 'body',
      ),
      3 => array(
        'source' => 'alpha',
        'target' => 'field_alpha',
      ),
      4 => array(
        'source' => 'beta',
        'target' => 'field_beta',
      ),
      5 => array(
        'source' => 'gamma',
        'target' => 'field_gamma',
      ),
      6 => array(
        'source' => 'delta',
        'target' => 'field_delta',
      ),
    ));

    // Import CSV file.
    $fid = $this->importFile('csv', $this->absolutePath() . '/tests/feeds/content.csv');
    $this->assertText('Created 2 nodes');

    // Check the two imported files.
    $this->drupalGet('node/1/edit');
    $this->assertNodeFieldValue('alpha', 'Lorem');
    $this->assertNodeFieldValue('beta', '42');
    $this->assertNodeFieldValue('gamma', '4.20');
    $this->assertNodeFieldValue('delta', '3.14159');

    $this->drupalGet('node/2/edit');
    $this->assertNodeFieldValue('alpha', 'Ut wisi');
    $this->assertNodeFieldValue('beta', '32');
    $this->assertNodeFieldValue('gamma', '1.20');
    $this->assertNodeFieldValue('delta', '5.62951');
  }

}
