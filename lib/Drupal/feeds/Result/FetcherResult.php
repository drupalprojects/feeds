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
    return $this->sanitizeRaw(file_get_contents($this->filePath));
  }

  /**
   * {@inheritdoc}
   */
  public function getFilePath() {
    if (!file_exists($this->filePath)) {
      throw new \RuntimeException(String::format('File @filepath is not accessible.', array('@filepath' => $this->filePath)));
    }
    return $this->sanitizeFile($this->filePath);
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
   * @param string $filepath
   *   The file path of the file to be sanitized.
   *
   * @return string
   *   The file path of the sanitized file.
   *
   * @throws \RuntimeException
   *   Thrown if the file is not writeable.
   */
  protected function sanitizeFile($filepath) {
    $handle = fopen($filepath, 'r');
    $line = fgets($handle);
    fclose($handle);

    // If BOM header is present, read entire contents of file and overwrite the
    // file with corrected contents.
    if (substr($line, 0, 3) == pack('CCC', 0xef, 0xbb, 0xbf)) {
      $contents = file_get_contents($filepath);
      $contents = substr($contents, 3);
      $status = file_put_contents($filepath, $contents);
      if ($status === FALSE) {
        throw new \RuntimeException(String::format('File @filepath is not writeable.', array('@filepath' => $filepath)));
      }
    }

    return $filepath;
  }

}
