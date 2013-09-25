<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\FeedsMapperDateTest.
 */

namespace Drupal\feeds\Tests;

use Drupal\feeds\FeedsMapperTestBase;
use Drupal\feeds\Plugin\Type\Scheduler\SchedulerInterface;

/**
 * Class for testing date target.
 *
 * @todo: Add test method iCal.
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
    $typename = $this->createContentType(array(), array('datetime' => 'datetime'));

    // Create and configure importer.
    $this->createImporterConfiguration('Date RSS', 'daterss');
    $this->setSettings('daterss', 'scheduler', array('import_period' => SchedulerInterface::SCHEDULE_NEVER), TRUE);
    $this->setPlugin('daterss', 'fetcher', 'upload');
    $edit = array(
      'processor[advanced][values][type]' => $typename,
    );
    $this->drupalPost('admin/structure/feeds/manage/daterss', $edit, t('Save'));
    // $this->setSettings('daterss', 'processor', array('values[type]' => $typename));
    $this->addMappings('daterss', array(
      0 => array(
        'target' => 'title',
        'map' => array(
          'value' => 'title',
        ),
      ),
      1 => array(
        'target' => 'body',
        'map' => array(
          'value' => 'description',
        ),
      ),
      2 => array(
        'target' => 'field_datetime',
        'map' => array(
          'value' => 'timestamp',
        ),
      ),
    ));

    $this->setSettings('daterss', 'fetcher', array('allowed_extensions' => 'rss2'));

    // Import CSV file.
    $fid = $this->importFile('daterss', $this->absolutePath() . '/tests/feeds/googlenewstz.rss2');
    $this->assertText('Created 6 ');

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
