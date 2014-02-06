<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Result\FetcherResultTest.
 */

namespace Drupal\feeds\Tests;

use Drupal\feeds\Result\FetcherResult;
use Drupal\feeds\Tests\FeedsUnitTestCase;

/**
 * @covers \Drupal\feeds\Result\FetcherResult
 */
class FetcherResultTest extends FeedsUnitTestCase {

  const FILE = 'tmp_file';

  public static function getInfo() {
    return array(
      'name' => 'Feeds: Fetcher result',
      'description' => 'Tests fetcher result.',
      'group' => 'Feeds',
    );
  }

  /**
   * @expectedException \RuntimeException
   */
  public function testGetRaw() {
    file_put_contents(static::FILE, pack('CCC', 0xef, 0xbb, 0xbf) . 'I am test data.');
    $result = new FetcherResult(static::FILE);
    $this->assertSame('I am test data.', $result->getRaw());

    // Throws exception.
    $result = new FetcherResult('IDONTEXIST');
    $result->getRaw();
  }

  /**
   * @expectedException \RuntimeException
   */
  public function testGetFilePath() {
    file_put_contents(static::FILE, pack('CCC', 0xef, 0xbb, 0xbf) . 'I am test data.');
    $result = new FetcherResult(static::FILE);
    $this->assertSame(static::FILE, $result->getFilePath());

    file_put_contents(static::FILE, pack('CCC', 0xef, 0xbb, 0xbf) . 'I am test data.');
    $result = new FetcherResult(static::FILE);
    chmod(static::FILE, 0444);
    $this->assertSame(static::FILE, $result->getFilePath());

    // Throws exception.
    $result = new FetcherResult('IDONTEXIST');
    $result->getFilePath();
  }

  /**
   * @expectedException \RuntimeException
   */
  public function testGetFilePathNonExistentFile() {
    // Throws exception.
    $result = new FetcherResult('IDONTEXIST');
    $result->getFilePath();
  }

}
