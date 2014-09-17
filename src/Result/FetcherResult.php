<?php

/**
 * @file
 * Contains \Drupal\feeds\Result\FetcherResult.
 */

namespace Drupal\feeds\Result;

use Drupal\Component\Utility\String;

/**
 * The default fetcher result object.
 */
class FetcherResult implements FetcherResultInterface {

  /**
   * The filepath of the fetched item.
   *
   * @var string
   */
  protected $filePath;

  /**
   * Constructs a new FetcherResult object.
   *
   * @param string $file_path
   *   The path to the result file.
   */
  public function __construct($file_path) {
    $this->filePath = $file_path;
  }

  /**
   * {@inheritdoc}
   */
  public function getRaw() {
    $raw = file_get_contents($this->filePath);
    if ($raw === FALSE) {
      $this->error('The file %file is not readable.');
    }
    return $this->sanitizeRaw($raw);
  }

  /**
   * {@inheritdoc}
   */
  public function getFilePath() {
    if (!file_exists($this->filePath)) {
      $this->error('File %filepath is not accessible.');
    }
    return $this->sanitizeFile();
  }

  /**
   * Sanitizes the raw content string.
   *
   * Currently supported sanitizations:
   * - Remove BOM header from UTF-8 files.
   *
   * @param string $raw
   *   The raw content string to be sanitized.
   *
   * @return string
   *   The sanitized content as a string.
   */
  protected function sanitizeRaw($raw) {
    if (substr($raw, 0, 3) == pack('CCC', 0xef, 0xbb, 0xbf)) {
      $raw = substr($raw, 3);
    }

    return $raw;
  }

  /**
   * Sanitizes the file in place.
   *
   * Currently supported sanitizations:
   * - Remove BOM header from UTF-8 files.
   *
   * @return string
   *   The file path of the sanitized file.
   *
   * @throws \RuntimeException
   *   Thrown if the file is not writeable.
   */
  protected function sanitizeFile() {
    $handle = fopen($this->filePath, 'r');

    // This should rarely happen since we already checked if the file exists.
    if ($handle === FALSE) {
      $this->error('File %filepath is not readable.');
    }
    $line = fgets($handle);
    fclose($handle);

    // If BOM header is present, read entire contents of file and overwrite the
    // file with corrected contents.
    if (substr($line, 0, 3) == pack('CCC', 0xef, 0xbb, 0xbf)) {
      $contents = file_get_contents($this->filePath);
      $contents = substr($contents, 3);
      $status = file_put_contents($this->filePath, $contents);
      if ($status === FALSE) {
        $this->error('File %filepath is not writeable.');
      }
    }

    return $this->filePath;
  }

  /**
   * Throws an exception on file error.
   *
   * @param string $message
   *   The message to display. The filepath will be substituted in for
   *   %filepath.
   *
   * @throws \RuntimeException
   *   This always throws an exception, that's its job.
   */
  protected function error($message) {
    throw new \RuntimeException(String::format($message, array('%filepath' => $this->filePath)));
  }

}
