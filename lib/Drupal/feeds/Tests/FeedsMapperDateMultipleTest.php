<?php

/**
 * @file
 * Test case for CCK date multi-field mapper mappers/date.inc.
 */

namespace Drupal\feeds\Tests;

/**
 * Class for testing Feeds <em>content</em> mapper.
 *
 * @todo: Add test method iCal
 * @todo: Add test method for end date
 */
class FeedsMapperDateMultipleTest extends FeedsMapperTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'field',
    'field_ui',
    'datetime',
    'job_scheduler',
    'feeds_ui',
  );

  public static function getInfo() {
    return array(
      'name' => 'Mapper: Date, multi value fields',
      'description' => 'Test Feeds Mapper support for CCK multi valiue Date fields.',
      'group' => 'Feeds',
    );
  }

  public function setUp() {
    parent::setUp();
    config('system.timezone')
      ->set('default', 'UTC')
      ->save();
  }

  /**
   * Testing import by loading a 4 item XML file.
   */
  public function test() {
    $this->drupalGet('admin/config/regional/settings');

    // Create content type.
    $typename = $this->createContentType(array(), array(
      'date' => 'datetime',
      'settings' => array(
        'field[container][cardinality]' => -1,
      ),
    ));

    // Create and configure importer.
    $this->createImporterConfiguration('Multi dates', 'multidates');
    $this->setSettings('multidates', NULL, array(
      'content_type' => '',
      'import_period' => FEEDS_SCHEDULE_NEVER,
    ));
    $this->setPlugin('multidates', 'FeedsFileFetcher');
    $this->setPlugin('multidates', 'FeedsXPathParserXML');

    $this->setSettings('multidates', 'FeedsNodeProcessor', array(
      'bundle' => $typename,
    ));
    $this->addMappings('multidates', array(
      0 => array(
        'source' => 'xpathparser:0',
        'target' => 'title',
      ),
      1 => array(
        'source' => 'xpathparser:1',
        'target' => 'guid',
      ),
      2 => array(
        'source' => 'xpathparser:2',
        'target' => 'field_date:start',
      ),
    ));

    $edit = array(
      'xpath[context]' => '//item',
      'xpath[sources][xpathparser:0]' => 'title',
      'xpath[sources][xpathparser:1]' => 'guid',
      'xpath[sources][xpathparser:2]' => 'date',
      'xpath[allow_override]' => FALSE,
    );
    $this->setSettings('multidates', 'FeedsXPathParserXML', $edit);

    $edit = array(
      'allowed_extensions' => 'xml',
      'directory' => 'public://feeds',
    );
    $this->setSettings('multidates', 'FeedsFileFetcher', $edit);

    // Import XML file.
    $this->importFile('multidates', $this->absolutePath() . '/tests/feeds/multi-date.xml');
    $this->assertText('Created 4 nodes');

    // Check the imported nodes.
    $values = array(
      1 => array(
        '01/06/2010 - 15:00',
        '01/07/2010 - 15:15',
      ),
      2 => array(
        '01/06/2010 - 15:00',
        '01/07/2010 - 15:00',
        '01/08/2010 - 15:00',
        '01/09/2010 - 15:00',
      ),
      3 => array(
        '', // Bogus date was filtered out.
      ),
      4 => array(
        '01/06/2010 - 14:00',
      )
    );
    foreach ($values as $v => $key) {
      $this->drupalGet("node/$v/edit");
      foreach ($key as $delta => $value) {
        $this->assertFieldById('edit-field-date-und-' . $delta . '-value-date', $value);
      }
    }
  }

}
