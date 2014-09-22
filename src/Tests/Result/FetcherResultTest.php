<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Result\FetcherResultTest.
 */

namespace Drupal\feeds\Tests\Result;

use Drupal\feeds\Result\FetcherResult;
use Drupal\feeds\Tests\FeedsUnitTestCase;

/**
 * @covers \Drupal\feeds\Result\FetcherResult
 */
class FetcherResultTest extends FeedsUnitTestCase {

  const FILE = 'tmp_file';

  public static function getInfo() {
    return array(
      'name' => 'Result: fetcher',
      'description' => 'Tests fetcher result.',
      'group' => 'Feeds',
    );
  }

  public function testGetRaw() {
    file_put_contents(static::FILE, pack('CCC', 0xef, 0xbb, 0xbf) . 'I am test data.');
    $result = new FetcherResult(static::FILE);
    $this->assertSame('I am test data.', $result->getRaw());
  }

  public function testGetFilePath() {
    file_put_contents(static::FILE, 'I am test data.');
    $result = new FetcherResult(static::FILE);
    $this->assertSame(static::FILE, $result->getFilePath());
  }

  public function testGetSanitizedFilePath() {
    file_put_contents(static::FILE, pack('CCC', 0xef, 0xbb, 0xbf) . 'I am test data.');
    $result = new FetcherResult(static::FILE);
    $this->assertSame('I am test data.', file_get_contents($result->getFilePath()));
  }

  /**
   * @expectedException \RuntimeException
   */
  public function testNonExistantFile() {
    $result = new FetcherResult('IDONOTEXIST');
    $result->getRaw();
  }

  /**
   * @expectedException \RuntimeException
   */
  public function testNonReadableFile() {
    file_put_contents(static::FILE, 'I am test data.');
    chmod(static::FILE, 000);
    $result = new FetcherResult(static::FILE);
    $result->getRaw();
  }

  /**
   * @expectedException \RuntimeException
   */
  public function testNonWritableFile() {
    file_put_contents(static::FILE, pack('CCC', 0xef, 0xbb, 0xbf) . 'I am test data.');
    chmod(static::FILE, 0444);
    $result = new FetcherResult(static::FILE);
    $result->getFilePath();
  }

}
