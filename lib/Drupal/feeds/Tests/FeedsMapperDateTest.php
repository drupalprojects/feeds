<?php

namespace Drupal\feeds\Tests;

/**
 * @file
 * Test case for date field mapper mappers/date.inc.
 */

/**
 * Class for testing Feeds <em>content</em> mapper.
 *
 * @todo: Add test method iCal
 * @todo: Add test method for end date
 */
class FeedsMapperDateTest extends FeedsMapperTestBase {

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
      'name' => 'Mapper: Date',
      'description' => 'Test Feeds Mapper support for Date fields.',
      'group' => 'Feeds',
    );
  }

  public function setUp() {
    parent::setUp();

    config('system.timezone')
      ->set('default', 'UTC')
      ->set('user.configurable', FALSE)
      ->save();
  }

  /**
   * Basic test loading a single entry CSV file.
   */
  public function test() {
    // Create content type.
    $typename = $this->createContentType(array(), array(
      'datetime' => 'datetime',
    ));

    // Create and configure importer.
    $this->createImporterConfiguration('Date RSS', 'daterss');
    $this->setSettings('daterss', NULL, array(
      'import_period' => FEEDS_SCHEDULE_NEVER,
    ));
    $this->setPlugin('daterss', 'fetcher', 'file');
    $this->setSettings('daterss', 'processor', array(
      'bundle' => $typename,
    ));
    $this->addMappings('daterss', array(
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
        'target' => 'field_datetime',
      ),
    ));

    $this->setSettings('daterss', 'fetcher', array('allowed_extensions' => 'rss2'));

    // Import CSV file.
    $fid = $this->importFile('daterss', $this->absolutePath() . '/tests/feeds/googlenewstz.rss2');
    $this->assertText('Created 6 nodes');

    // Check the imported nodes.
    $dates = array(
      '2010-01-06',
      '2010-01-06',
      '2010-01-06',
      '2010-01-06',
      '2010-01-06',
      '2010-01-07',
    );

    $times = array(
      '19:26:27',
      '10:21:20',
      '13:42:47',
      '06:05:40',
      '11:26:39',
      '00:26:26',
    );

    for ($i = 1; $i <= 6; $i++) {
      $this->drupalGet("node/$i/edit");
      $this->assertNodeFieldValue('datetime', $dates[$i - 1]);
      $this->assertFieldByName('field_datetime[und][0][value][time]', $times[$i - 1]);
    }
  }

  protected function getFormFieldsNames($field_name, $index) {
    if (in_array($field_name, array('datetime'))) {
      return array("field_{$field_name}[und][{$index}][value][date]");
    }
    else {
      return parent::getFormFieldsNames($field_name, $index);
    }
  }

}
