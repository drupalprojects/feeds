<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Component\CsvParserTest.
 */

namespace Drupal\feeds\Tests\Component;

use Drupal\feeds\Component\CsvParser;
use Drupal\feeds\Tests\FeedsUnitTestCase;

/**
 * @covers \Drupal\feeds\Component\CsvParser
 * @group Feeds
 */
class CsvParserTest extends FeedsUnitTestCase {

  /**
   * @dataProvider provider
   */
  public function testAlternateLineEnding(array $expected, $ending) {
    $text = file_get_contents(dirname(dirname(dirname(dirname(__FILE__)))) . '/tests/resources/csv-example.xml');
    $text = str_replace("\r\n", $ending, $text);
    $parser = CsvParser::createFromString($text)
      ->setLineLimit(4);

    $first = array_slice($expected, 0, 4);

    $rows = $parser->parse();
    $this->assertSame(count($rows), count($first));
    foreach ($rows as $delta => $row) {
      $this->assertSame($first[$delta], $row);
    }

    // Test second batch.
    $last_pos = $parser->lastLinePos();

    $parser = CsvParser::createFromString($text)
      ->setStartByte($last_pos);
    $rows = $parser->parse();

    $second = array_slice($expected, 4);
    $this->assertSame(count($rows), count($second));
    foreach ($rows as $delta => $row) {
      $this->assertSame($second[$delta], $row);
    }
  }

  public function provider() {
    $expected = [
      ['Header A', 'Header B', 'Header C'],
      ['"1"', '"2"', '"3"'],
      ['qu"ote', 'qu"ote', 'qu"ote'],
      ["\r\n\r\nline1", "\r\n\r\nline2", "\r\n\r\nline3"],
      ["new\r\nline 1", "new\r\nline 2", "new\r\nline 3"],
      ["\r\n\r\nline1\r\n\r\n", "\r\n\r\nline2\r\n\r\n", "\r\n\r\nline3\r\n\r\n"],
    ];

    $unix = $expected;
    array_walk_recursive($unix, function (&$item, $key) {
      $item = str_replace("\r\n", "\n", $item);
    });

    $mac = $expected;
    array_walk_recursive($mac, function (&$item, $key) {
      $item = str_replace("\r\n", "\r", $item);
    });

    return [
      [$expected, "\r\n"],
      [$unix, "\n"],
      [$mac, "\r"],
    ];
  }

  public function testHasHeader() {
    $file = dirname(dirname(dirname(dirname(__FILE__)))) . '/tests/resources/csv-example.xml';
    $parser = CsvParser::createFromFilePath($file)
      ->setLineLimit(10)
      ->setHasHeader();

    $rows = $parser->parse();
    $this->assertSame(count($rows), 5);
    $this->assertSame(['Header A', 'Header B', 'Header C'], $parser->getHeader());
  }

  public function testAlternateSeparator() {
    // This implicitly tests lines without a newline.
    $rows = CsvParser::createFromString("a*b*c")
      ->setDelimiter('*')
      ->parse();
    $this->assertSame(['a', 'b', 'c'], $rows[0]);
  }

  /**
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidFilePath() {
    CsvParser::createFromFilePath('beep boop');
  }

  /**
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidResourcePath() {
    new CsvParser('beep boop');
  }

}
