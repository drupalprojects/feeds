<?php

/**
 * @file
 * Contains \Drupal\feeds\HTTPFetcherResult.
 */

namespace Drupal\feeds;

/**
 * Result of HTTPFetcher::fetch().
 */
class HTTPFetcherResult extends FeedsFetcherResult {
  protected $url;
  protected $timeout;

  /**
   * Constructs an HTTPFetcherResult object.
   */
  public function __construct($url = NULL) {
    $this->url = $url;
    parent::__construct('');
  }

  /**
   * {@inheritdoc}
   */
  public function getRaw() {
    $result = HTTPRequest::Get($this->url, NULL, NULL, NULL, $this->timeout);
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
