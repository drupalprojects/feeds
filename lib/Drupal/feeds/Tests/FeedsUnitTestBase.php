<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\FeedsUnitTestBase.
 */

namespace Drupal\feeds\Tests;

use Drupal\feeds\Utility\HTTPRequest;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Base unit test class for Feeds.
 */
class FeedsUnitTestBase extends DrupalUnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Unit tests',
      'description' => 'Test basic low-level Feeds module functionality.',
      'group' => 'Feeds',
    );
  }

  /**
   * Tests valid absolute urls.
   *
   * @see ValidUrlTestCase
   *
   * @todo Remove when http://drupal.org/node/1191252 is fixed.
   */
  function testFeedsValidURL() {
    drupal_load('module', 'feeds');
    $url_schemes = array('http', 'https', 'ftp', 'feed', 'webcal');
    $valid_absolute_urls = array(
      'example.com',
      'www.example.com',
      'ex-ample.com',
      '3xampl3.com',
      'example.com/paren(the)sis',
      'example.com/index.html#pagetop',
      'example.com:8080',
      'subdomain.example.com',
      'example.com/index.php?q=node',
      'example.com/index.php?q=node&param=false',
      'user@www.example.com',
      'user:pass@www.example.com:8080/login.php?do=login&style=%23#pagetop',
      '127.0.0.1',
      'example.org?',
      'john%20doe:secret:foo@example.org/',
      'example.org/~,$\'*;',
      'caf%C3%A9.example.org',
      '[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]:80/index.html',
      'graph.asfdasdfasdf.com/blarg/feed?access_token=133283760145143|tGew8jbxi1ctfVlYh35CPYij1eE',
    );

    foreach ($url_schemes as $scheme) {
      foreach ($valid_absolute_urls as $url) {
        $test_url = $scheme . '://' . $url;
        $valid_url = HTTPRequest::validUrl($test_url, TRUE);
        $this->assertTrue($valid_url, t('@url is a valid url.', array('@url' => $test_url)));
      }
    }

    $invalid_ablosule_urls = array(
      '',
      'ex!ample.com',
      'ex%ample.com',
    );

    foreach ($url_schemes as $scheme) {
      foreach ($invalid_ablosule_urls as $url) {
        $test_url = $scheme . '://' . $url;
        $valid_url = HTTPRequest::validUrl($test_url, TRUE);
        $this->assertFalse($valid_url, t('@url is NOT a valid url.', array('@url' => $test_url)));
      }
    }
  }

}
