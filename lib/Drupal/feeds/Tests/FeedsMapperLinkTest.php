<?php

/**
 * @file
 * Test case for CCK link mapper mappers/date.inc.
 */

namespace Drupal\feeds\Tests;

/**
 * Class for testing Feeds <em>link</em> mapper.
 */
class FeedsMapperLinkTest extends FeedsMapperTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'field',
    'field_ui',
    'link',
    'job_scheduler',
    'feeds_ui',
  );

  public static function getInfo() {
    return array(
      'name' => 'Mapper: Link',
      'description' => 'Test Feeds Mapper support for Link fields.',
      'group' => 'Feeds',
    );
  }

  /**
   * Basic test loading a single entry CSV file.
   */
  public function test() {
    // Create content type.
    $typename = $this->createContentType(array(), array(
      'alpha' => array(
        'type' => 'link',
        'instance_settings' => array(
          'instance[settings][title]' => 2,
        ),
      ),
      'beta' => array(
        'type' => 'link',
        'instance_settings' => array(
          'instance[settings][title]' => 0,
        ),
      ),
      'gamma' => array(
        'type' => 'link',
        'instance_settings' => array(
          'instance[settings][title]' => 1,
        ),
      ),
    ));

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
        'source' => 'description',
        'target' => 'body',
      ),
      3 => array(
        'source' => 'url',
        'target' => 'field_alpha:url',
      ),
      4 => array(
        'source' => 'title',
        'target' => 'field_alpha:title',
      ),
      5 => array(
        'source' => 'url',
        'target' => 'field_beta:url',
      ),
      6 => array(
        'source' => 'url',
        'target' => 'field_gamma:url',
      ),
      7 => array(
        'source' => 'title',
        'target' => 'field_gamma:title',
      ),
    ));

    // Import RSS file.
    $fid = $this->createFeed();
    // Assert 10 items aggregated after creation of the node.
    $this->assertText('Created 10 nodes');

    // Edit the imported node.
    $this->drupalGet('node/1/edit');

    $url = 'http://developmentseed.org/blog/2009/oct/06/open-atrium-translation-workflow-two-way-updating';
    $title = 'Open Atrium Translation Workflow: Two Way Translation Updates';
    $this->assertNodeFieldValue('alpha', array('url' => $url, 'title' => $title));
    $this->assertNodeFieldValue('beta', array('url' => $url));
    $this->assertNodeFieldValue('gamma', array('url' => $url, 'title' => $title));
  }

  /**
   * Override parent::getFormFieldsNames().
   */
  protected function getFormFieldsNames($field_name, $index) {
    if (in_array($field_name, array('alpha', 'beta', 'gamma'))) {
      $fields = array("field_{$field_name}[und][{$index}][url]");
      if (in_array($field_name, array('alpha', 'gamma'))) {
        $fields[] = "field_{$field_name}[und][{$index}][title]";
      }
      return $fields;
    }
    else {
      return parent::getFormFieldsNames($field_name, $index);
    }
  }

  /**
   * Override parent::getFormFieldsValues().
   */
  protected function getFormFieldsValues($field_name, $value) {
    if (in_array($field_name, array('alpha', 'beta', 'gamma', 'omega'))) {
      if (!is_array($value)) {
        $value = array('url' => $value);
      }
      $values = array($value['url']);
      if (in_array($field_name, array('alpha', 'gamma'))) {
        $values[] = isset($value['title']) ? $value['title'] : '';
      }
      return $values;
    }
    else {
      return parent::getFormFieldsValues($field_name, $index);
    }
  }

}
