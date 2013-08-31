<?php

/**
 * @file
 * Contains \Drupal\feeds\Component\ParserCSVIterator.
 *
 * @todo There is not reason to be using the Iterator interface here. It's a
 *   nice idea, but the consumer is ignoring it.
 */

namespace Drupal\feeds\Component;

/**
 * Text lines from file iterator.
 */
class ParserCSVIterator implements \Iterator {

  /**
   * The file handle of the file being operated on.
   *
   * @var resource
   */
  protected $handle;

  /**
   * The current line being read.
   *
   * @var string
   */
  protected $currentLine;

  /**
   * The byte position in the file.
   *
   * @var int
   */
  protected $currentPosition;

  /**
   * Constructs a ParserCSVIterator object.
   *
   * @param string $filepath
   *   The path to the CSV file to iterate.
   */
  public function __construct($filepath) {
    if (is_resource($filepath)) {
      $this->handle = $filepath;
    }
    else {
      $this->handle = fopen($filepath, 'r');
    }
  }

  /**
   * Destructs a ParserCSVIterator object.
   *
   * Close the file handler on destruction.
   */
  public function __destruct() {
    if ($this->handle) {
      fclose($this->handle);
    }
  }

  /**
   * Implements \Iterator::current().
   */
  public function current() {
    return $this->currentLine;
  }

  /**
   * Implements \Iterator::key().
   */
  public function key() {
    return 'line';
  }

  /**
   * Implements \Iterator::next().
   */
  public function next() {
    if ($this->handle) {
      $this->currentLine = feof($this->handle) ? NULL : fgets($this->handle);
      $this->currentPosition = ftell($this->handle);
      return $this->currentLine;
    }
  }

  /**
   * Implements \Iterator::rewind().
   *
   * This extends the interface to allow arbitrary positioning.
   *
   * @param int $position
   *   (optional) The position in the file to seek to. Defaults to 0.
   */
  public function rewind($position = 0) {
    if ($this->handle) {
      fseek($this->handle, $position);
      $this->next();
    }
  }

  /**
   * Implements \Iterator::valid().
   */
  public function valid() {
    return isset($this->currentLine);
  }

  /**
   * Returns the current position in the file.
   *
   * @return int
   *   The current byte position in the file.
   */
  public function currentPosition() {
    return $this->currentPosition;
  }

}
