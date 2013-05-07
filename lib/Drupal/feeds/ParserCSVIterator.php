<?php

namespace Drupal\feeds;

use Iterator;

/**
 * Text lines from file iterator.
 */
class ParserCSVIterator implements Iterator {
  private $handle;
  private $currentLine;
  private $currentPos;

  public function __construct($filepath) {
    $this->handle = fopen($filepath, 'r');
    $this->currentLine = NULL;
    $this->currentPos = NULL;
  }

  function __destruct() {
    if ($this->handle) {
      fclose($this->handle);
    }
  }

  public function rewind($pos = 0) {
    if ($this->handle) {
      fseek($this->handle, $pos);
      $this->next();
    }
  }

  public function next() {
    if ($this->handle) {
      $this->currentLine = feof($this->handle) ? NULL : fgets($this->handle);
      $this->currentPos = ftell($this->handle);
      return $this->currentLine;
    }
  }

  public function valid() {
    return isset($this->currentLine);
  }

  public function current() {
    return $this->currentLine;
  }

  public function currentPos() {
    return $this->currentPos;
  }

  public function key() {
    return 'line';
  }
}
