<?php

namespace Drupal\feeds;

/**
 * Base class for all fetcher results.
 */
class FeedsFetcherResult extends FeedsResult {
  protected $raw;
  protected $file_path;

  /**
   * Constructor.
   */
  public function __construct($raw) {
    $this->raw = $raw;
  }

  /**
   * @return
   *   The raw content from the source as a string.
   *
   * @throws Exception
   *   Extending classes MAY throw an exception if a problem occurred.
   */
  public function getRaw() {
    return $this->sanitizeRaw($this->raw);
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
    if (!isset($this->file_path)) {
      $destination = 'public://feeds';
      if (!file_prepare_directory($destination, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
        throw new Exception(t('Feeds directory either cannot be created or is not writable.'));
      }
      $this->file_path = FALSE;
      if ($file = file_save_data($this->getRaw(), $destination . '/' . get_class($this) . REQUEST_TIME)) {
        $file->status = 0;
        $file->save();
        $this->file_path = $file->uri;
      }
      else {
        throw new Exception(t('Cannot write content to %dest', array('%dest' => $destination)));
      }
    }
    return $this->sanitizeFile($this->file_path);
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
