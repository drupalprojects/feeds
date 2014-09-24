<?php

/**
 * @file
 * Contains \Drupal\feeds\Component\CsvParser.
 */

namespace Drupal\feeds\Component;

/**
 * Parses an RFC 4180 style CSV file.
 *
 * http://tools.ietf.org/html/rfc4180
 */
class CsvParser {

  /**
   * The column delimeter.
   *
   * @var string
   */
  protected $delimiter = ',';

  /**
   * Whether or not the first line contains a header.
   *
   * @var bool
   */
  protected $hasHeader = FALSE;

  /**
   * The position in the file to start from.
   *
   * @var int
   */
  protected $startByte = 0;

  /**
   * The limit of the number of lines to parse. 0 means there is not limit.
   *
   * @var int
   */
  protected $lineLimit = 0;

  /**
   * The file handle to the CSV file.
   *
   * @var resource
   */
  protected $handle;

  /**
   * Constructs a CsvParser object.
   *
   * @param resource $stream
   *   An open file handle.
   */
  public function __construct($stream) {
    if (!is_resource($stream)) {
      throw new \InvalidArgumentException('Stream must be a resource.');
    }
    $this->handle = $stream;
  }

  /**
   * Creates a CsvParser object from a file path.
   *
   * @param string $filepath
   *   The file path.
   *
   * @return \Drupal\feeds\Component\CsvParser
   *   A new CsvParser object.
   */
  public static function createFromFilePath($filepath) {
    if (!file_exists($filepath) || !is_readable($filepath)) {
      throw new \InvalidArgumentException('Filepath must exist and be readable.');
    }

    $previous = ini_set('auto_detect_line_endings', '1');
    $stream = fopen($filepath, 'r');
    ini_set('auto_detect_line_endings', $previous);

    return new static($stream);
  }

  /**
   * Creates a CsvParser object from a string.
   *
   * @param string $string
   *   The in-memory contents of a CSV file.
   *
   * @return \Drupal\feeds\Component\CsvParser
   *   A new CsvParser object.
   */
  public static function createFromString($string) {
    $previous = ini_set('auto_detect_line_endings', '1');
    $stream = fopen('php://temp', 'r+');
    ini_set('auto_detect_line_endings', $previous);

    fwrite($stream, $string);
    fseek($stream, 0);
    return new static($stream);
  }

  /**
   * Destructs a CsvParser object,
   */
  public function __destruct() {
    if (is_resource($this->handle)) {
      fclose($this->handle);
    }
  }

  /**
   * Sets the column delimiter string.
   *
   * @param string $delimiter
   *   By default, the comma (',') is used as delimiter.
   *
   * @return $this
   */
  public function setDelimiter($delimiter) {
    $this->delimiter = $delimiter;
    return $this;
  }

  /**
   * Sets whether or not the CSV file contains a header.
   *
   * @param bool $has_header
   *   (optional) Whether or the CSV file has a header. Defaults to true.
   *
   * @return $this
   */
  public function setHasHeader($has_header = TRUE) {
    $this->hasHeader = (bool) $has_header;
    return $this;
  }

  /**
   * Returns the header row.
   *
   * @return array
   *   A list of the header names.
   */
  public function getHeader() {
    $prev = $this->lastLinePos();

    fseek($this->handle, 0);
    $header = $this->parseLine($this->readLine());
    fseek($this->handle, $prev);

    return $header;
  }

  /**
   * Defines the number of lines to parse in one operation.
   *
   * By default, all lines of a file are being parsed.
   *
   * @param int $lines
   *   The number of lines to parse in one operation.
   *
   * @return $this
   */
  public function setLineLimit($lines) {
    $this->lineLimit = (int) $lines;
    return $this;
  }

  /**
   * Gets the byte number where the parser left off after last parse() call.
   *
   * @return int
   *   0 if all lines or no line has been parsed, the byte position of where a
   *   timeout or the line limit has been reached, otherwise. This position can
   *   be used to set the start byte for the next iteration after parse() has
   *   reached the line limit set with setLineLimit().
   */
  public function lastLinePos() {
    return ftell($this->handle);
  }

  /**
   * Sets the byte where file should be started at.
   *
   * Useful when parsing a file in batches.
   *
   * @param int $start
   *   The byte position to start parsing.
   *
   * @return $this
   */
  public function setStartByte($start) {
    $this->startByte = (int) $start;
    return $this;
  }

  /**
   * Parses CSV files into a two dimensional array.
   *
   * @return array
   *   Two dimensional array that contains the data in the CSV file.
   */
  public function parse() {
    if ($this->hasHeader) {
      fseek($this->handle, 0);
      $this->parseLine($this->readLine());
    }

    if ($this->startByte > $this->lastLinePos()) {
      fseek($this->handle, $this->startByte);
    }

    $lines_read = 0;
    $rows = [];
    while ($line = $this->readLine()) {
      // Skip empty new lines.
      if (!strlen(rtrim($line, "\r\n"))) {
        continue;
      }

      $rows[] = $this->parseLine($line);
      $lines_read++;

      if ($lines_read === $this->lineLimit) {
        break;
      }
    }

    return $rows;
  }

  /**
   * Parses a single CSV line.
   *
   * @param string $line
   *   A line from a CSV file.
   * @param bool $in_quotes
   *   Do not use. For recursion only.
   * @param string $field
   *   Do not use. For recursion only.
   * @param array $fields
   *   Do not use. For recursion only.
   *
   * @return array
   *   The list of cells in the CSV row.
   */
  protected function parseLine($line, $in_quotes = FALSE, $field = '', $fields = []) {
    $line_length = strlen($line);

    // Traverse the line byte-by-byte.
    for ($index = 0; $index < $line_length; $index++) {
      $byte = $line[$index];
      $next_byte = isset($line[$index + 1]) ? $line[$index + 1] : '';

      // Found an escaped double quote.
      if ($byte === '"' && $next_byte === '"') {
        $field .= '"';
        $index++;
        continue;
      }

      // Beginning or ending a quoted field.
      if ($byte === '"' && $next_byte !== '"') {
        $in_quotes = !$in_quotes;
        continue;
      }

      // Ending a field.
      if (!$in_quotes && $byte === $this->delimiter) {
        $fields[] = $field;
        $field = '';
        continue;
      }

      // End of this line.
      if (!$in_quotes && $next_byte === '') {
        // Don't save the last newline, but don't remove all newlines.
        if ($byte === "\n") {
          // Check for windows line ending.
          $field = substr($field, -1) === "\r" ? substr($field, 0, -1) : $field;
        }
        elseif ($byte === "\r") {
          // Mac line endings.
        }
        else {
          // Line ended without a trailing newline.
          $field .= $byte;
        }

        $fields[] = $field;
        $field = '';
        continue;
      }

      $field .= $byte;
    }

    // If we're still in quotes after the line is read continue reading on the
    // next line. Check that we're not at the end of a malformed file.
    if ($in_quotes && $line = $this->readLine()) {
      $fields = $this->parseLine($line, $in_quotes, $field, $fields);
    }

    return $fields;
  }

  /**
   * Returns a new line from the CSV file.
   *
   * @return string|bool
   *   Returns the next line in the file, or false if the end has been reached.
   *
   * @todo Add encoding conversion.
   */
  protected function readLine() {
    return fgets($this->handle);
  }

}
