<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\GenericOPMLParserTest.
 */

namespace Drupal\feeds\Tests;

use Drupal\feeds\Component\GenericOPMLParser;
use Drupal\simpletest\UnitTestBase;

/**
 * Base unit test class for Feeds.
 */
class GenericOPMLParserTest extends UnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Unit tests: Generic OPML Parser',
      'description' => 'Tests the ParserOPML class.',
      'group' => 'Feeds',
    );
  }

  /**
   * Tests parsing a nested OPML feed file.
   */
  function testFeed() {

    $xml = file_get_contents(drupal_get_path('module', 'feeds') . '/tests/feeds/subscriptions.xml');
    $parser = new GenericOPMLParser($xml);
    $result = $parser->parse();
    $this->assertEqual($result['head']['#title'], 'subscriptions in Google Reader');
    $this->assertEqual($result['outlines'][0]['#title'], 'Cabinet of Wonders');
    $this->assertEqual($result['outlines'][0]['#text'], 'Cabinet of Wonders');
    $this->assertEqual($result['outlines'][3]['#title'], 'mashup blogs');
    $this->assertEqual($result['outlines'][3]['outlines'][0]['#title'], 'Beatmixed');
    $this->assertEqual($result['outlines'][3]['outlines'][0]['#type'], 'rss');

    $this->assertEqual($result['outlines'][4]['#title'], 'Software Issues');
    $this->assertEqual($result['outlines'][4]['outlines'][0]['#title'], 'Orbited:');
    $this->assertEqual($result['outlines'][4]['outlines'][1]['#title'], 'news');
    $this->assertEqual($result['outlines'][4]['outlines'][1]['outlines'][0]['#title'], 'DesktopLinux.com');
  }

}
