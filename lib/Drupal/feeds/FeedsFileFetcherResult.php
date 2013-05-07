<?php

/**
 * @file
 * Home of the FeedsFileFetcher and related classes.
 */

namespace Drupal\feeds;

use Exception;

/**
 * Definition of the import batch object created on the fetching stage by
 * FeedsFileFetcher.
 */
class FeedsFileFetcherResult extends FeedsFetcherResult {
  /**
   * Constructor.
   */
  public function __construct($file_path) {
    parent::__construct('');
    $this->file_path = $file_path;
  }

  /**
   * Overrides parent::getRaw().
   */
  public function getRaw() {
    return $this->sanitizeRaw(file_get_contents($this->file_path));
  }

  /**
   * Overrides parent::getFilePath().
   */
  public function getFilePath() {
    if (!file_exists($this->file_path)) {
      throw new Exception(t('File @filepath is not accessible.', array('@filepath' => $this->file_path)));
    }
    return $this->sanitizeFile($this->file_path);
  }
}
