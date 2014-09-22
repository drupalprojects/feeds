<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Result\RawFetcherResultTest.
 */

namespace Drupal\feeds\Tests\Result {

use Drupal\feeds\Result\RawFetcherResult;
use Drupal\feeds\Tests\FeedsUnitTestCase;

/**
 * @coversDefaultClass \Drupal\feeds\Result\RawFetcherResult
 * @group Feeds
 */
class RawFetcherResultTest extends FeedsUnitTestCase {

  const FILE = 'feeds-tmp';

  public function testRaw() {
    $result = new RawFetcherResult('raw text');
    $this->assertSame($result->getRaw(), 'raw text');
  }

  public function testFilePath() {
    $result = new RawFetcherResult('raw text');
    $this->assertSame(file_get_contents($result->getFilePath()), 'raw text');

    // Call again to see if exception is thrown.
    $this->assertSame(file_get_contents($result->getFilePath()), 'raw text');
  }

}
}

namespace {
  if (!function_exists('drupal_tempnam')) {
    function drupal_tempnam() {
      // Ensure only called once.
      static $called = FALSE;
      if ($called) {
        throw new \Exception();
      }
      $called = TRUE;
      return \Drupal\feeds\Tests\Result\RawFetcherResultTest::FILE;
    }
  }
}
