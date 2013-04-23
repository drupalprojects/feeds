<?php

namespace Drupal\feeds;

/**
 * Result of FeedsHTTPFetcher::fetch().
 */
class FeedsHTTPFetcherResult extends FeedsFetcherResult {
  protected $url;
  protected $file_path;
  protected $timeout;

  /**
   * Constructor.
   */
  public function __construct($url = NULL) {
    $this->url = $url;
    parent::__construct('');
  }

  /**
   * Overrides FeedsFetcherResult::getRaw();
   */
  public function getRaw() {
    feeds_include_library('http_request.inc', 'http_request');
    $result = http_request_get($this->url, NULL, NULL, NULL, $this->timeout);
    if (!in_array($result->code, array(200, 201, 202, 203, 204, 205, 206))) {
      throw new \Exception(t('Download of @url failed with code !code.', array('@url' => $this->url, '!code' => $result->code)));
    }
    return $this->sanitizeRaw($result->data);
  }

  public function getTimeout() {
    return $this->timeout;
  }

  public function setTimeout($timeout) {
    $this->timeout = $timeout;
  }
}
