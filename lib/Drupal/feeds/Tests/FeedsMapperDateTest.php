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

  // public function setUp() {
  //   parent::setUp(array('date_api', 'date'));
  //   variable_set('date_default_timezone', 'UTC');
  // }

  /**
   * Basic test loading a single entry CSV file.
   */
  public function test() {
    // Create content type.
    $typename = $this->createContentType(array(), array(
      'datetime' => 'datetime',
    ));

    // Hack to get date fields to not round to every 15 minutes.
    // foreach (array('date', 'datestamp') as $field) {
    //   $field = 'field_' . $field;
    //   $edit = array(
    //     'widget_type' => 'date_select',
    //   );
    //   $this->drupalPost('admin/structure/types/manage/' . $typename . '/fields/' . $field . '/widget-type', $edit, 'Continue');
    //   $edit = array(
    //     'instance[widget][settings][increment]' => 1,
    //   );
    //   $this->drupalPost('admin/structure/types/manage/' . $typename . '/fields/' . $field, $edit, 'Save settings');
    //   $edit = array(
    //     'widget_type' => 'date_text',
    //   );
    //   $this->drupalPost('admin/structure/types/manage/' . $typename . '/fields/' . $field . '/widget-type', $edit, 'Continue');
    // }

    // Create and configure importer.
    $this->createImporterConfiguration('Date RSS', 'daterss');
    $this->setSettings('daterss', NULL, array(
      'content_type' => '',
      'import_period' => FEEDS_SCHEDULE_NEVER,
    ));
    $this->setPlugin('daterss', 'file');
    $this->setSettings('daterss', 'node', array(
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
        'target' => 'field_datetime:start',
      ),
    ));

    $this->setSettings('daterss', 'file', array('allowed_extensions' => 'rss2'));

    // Import CSV file.
    $this->importFile('daterss', $this->absolutePath() . '/tests/feeds/googlenewstz.rss2');
    $this->assertText('Created 6 nodes');

    // Check the imported nodes.
    $values = array(
      '01/06/2010 - 19:26',
      '01/06/2010 - 10:21',
      '01/06/2010 - 13:42',
      '01/06/2010 - 06:05',
      '01/06/2010 - 11:26',
      '01/07/2010 - 00:26',
    );
    for ($i = 1; $i <= 6; $i++) {
      $this->drupalGet("node/$i/edit");
      $this->assertNodeFieldValue('date', $values[$i-1]);
      $this->assertNodeFieldValue('datestamp', $values[$i-1]);
    }
  }

  protected function getFormFieldsNames($field_name, $index) {
    if (in_array($field_name, array('date', 'datetime', 'datestamp'))) {
      return array("field_{$field_name}[und][{$index}][value][date]");
    }
    else {
      return parent::getFormFieldsNames($field_name, $index);
    }
  }
}
