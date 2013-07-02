<?php

/**
 * @file
 * Contains \Drupal\feeds\FeedsFetcherResult.
 */

namespace Drupal\feeds;

/**
 * Base class for all fetcher results.
 */
class FeedsFetcherResult {

  /**
   * The filepath of the fetched item.
   *
   * @var string
   */
  protected $filePath;

  /**
   * Constructs a new FeedsFetcherResult object.
   */
  public function __construct($file_path) {
    $this->filePath = $file_path;
  }

  /**
   * @return string
   *   The raw content from the source as a string.
   *
   * @throws Exception
   *   Extending classes MAY throw an exception if a problem occurred.
   */
  public function getRaw() {
    return $this->sanitizeRaw(file_get_contents($this->filePath));
  }

  /**
   * Get a path to a temporary file containing the resource provided by the
   * fetcher.
   *
   * File will be deleted after DRUPAL_MAXIMUM_TEMP_FILE_AGE.
   *
   * @return
   *   A path to a file containing the raw content as a source.
   *
   * @throws Exception
   *   If an unexpected problem occurred.
   */
  public function getFilePath() {
    if (!file_exists($this->filePath)) {
      throw new Exception(t('File @filepath is not accessible.', array('@filepath' => $this->file_path)));
    }
    return $this->sanitizeFile($this->filePath);
  }

  /**
   * Sanitize the raw content string. Currently supported sanitizations:
   *
   * - Remove BOM header from UTF-8 files.
   *
   * @param string $raw
   *   The raw content string to be sanitized.
   * @return
   *   The sanitized content as a string.
   */
  public function sanitizeRaw($raw) {
    if (substr($raw, 0, 3) == pack('CCC', 0xef, 0xbb, 0xbf)) {
      $raw = substr($raw, 3);
    }
    return $raw;
  }

  /**
   * Sanitize the file in place. Currently supported sanitizations:
   *
   * - Remove BOM header from UTF-8 files.
   *
   * @param string $filepath
   *   The file path of the file to be sanitized.
   * @return
   *   The file path of the sanitized file.
   */
  public function sanitizeFile($filepath) {
    $handle = fopen($filepath, 'r');
    $line = fgets($handle);
    fclose($handle);
    // If BOM header is present, read entire contents of file and overwrite
    // the file with corrected contents.
    if (substr($line, 0, 3) == pack('CCC', 0xef, 0xbb, 0xbf)) {
      $contents = file_get_contents($filepath);
      $contents = substr($contents, 3);
      $status = file_put_contents($filepath, $contents);
      if ($status === FALSE) {
        throw new Exception(t('File @filepath is not writeable.', array('@filepath' => $filepath)));
      }
    }
    return $filepath;
  }

}
