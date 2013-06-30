<?php

/**
 * @file
 * Contains \Drupal\feeds\HTTPRequest.
 *
 * @todo Remove this.
 */

namespace Drupal\feeds;

use Zend\Feed\Reader\FeedSet;

/**
 * Support caching, HTTP Basic Authentication, detection of RSS/Atom feeds,
 * redirects.
 */
class HTTPRequest {

  /**
   * Discovers RSS or atom feeds at the given URL.
   *
   * If document in given URL is an HTML document, function attempts to discover
   * RSS or Atom feeds.
   *
   * @param string $url
   *   The url of the feed to retrieve.
   * @param array $settings
   *   An optional array of settings. Valid options are: accept_invalid_cert.
   *
   * @return bool|string
   *   The discovered feed, or FALSE if the URL is not reachable or there was an
   *   error.
   */
  public static function getCommonSyndication($url, $settings = NULL) {

    $accept_invalid_cert = isset($settings['accept_invalid_cert']) ? $settings['accept_invalid_cert'] : FALSE;
    $download = static::get($url, NULL, NULL, $accept_invalid_cert);

    // Cannot get the feed, return.
    // static::get() always returns 200 even if its 304.
    if ($download->code != 200) {
      return FALSE;
    }

    // Drop the data into a seperate variable so all manipulations of the html
    // will not effect the actual object that exists in the static cache.
    // @see http_request_get.
    $downloaded_string = $download->data;
    // If this happens to be a feed then just return the url.
    if (static::isFeed($downloaded_string)) {
      return $url;
    }

    return static::findFeed($downloaded_string, $url);
  }

  /**
   * Gets the content from the given URL.
   *
   * @param string $url
   *   A valid URL (not only web URLs).
   * @param string $username
   *   If the URL uses authentication, supply the username.
   * @param string $password
   *   If the URL uses authentication, supply the password.
   * @param bool $accept_invalid_cert
   *   Whether to accept invalid certificates.
   * @param integer $timeout
   *   Timeout in seconds to wait for an HTTP get request to finish.
   *
   * @return stdClass
   *   An object that describes the data downloaded from $url.
   */
  public static function get($url, $username = NULL, $password = NULL, $accept_invalid_cert = FALSE, $timeout = NULL) {
    // Intra-pagedownload cache, avoid to download the same content twice within
    // one page download (it's possible, compatible and parse calls).
    static $download_cache = array();
    if (isset($download_cache[$url])) {
      return $download_cache[$url];
    }

    // Determine request timeout.
    $request_timeout = !empty($timeout) ? $timeout : variable_get('http_request_timeout', 30);

    if (!$username && valid_url($url, TRUE)) {
      // Handle password protected feeds.
      $url_parts = parse_url($url);
      if (!empty($url_parts['user'])) {
        $password = $url_parts['pass'];
        $username = $url_parts['user'];
      }
    }

    // Support the 'feed' and 'webcal' schemes by converting them into 'http'.
    $url = strtr($url, array('feed://' => 'http://', 'webcal://' => 'http://'));

    $request = \Drupal::httpClient()->get($url);

    // Only download and parse data if really needs refresh.
    // Based on "Last-Modified" and "If-Modified-Since".
    if ($cache = cache()->get('feeds_http_download_' . md5($url))) {
      $last_result = $cache->data;
      $last_headers = array_change_key_case($last_result->headers);

      if (!empty($last_headers['etag'])) {
        $request->addHeader('If-None-Match', $last_headers['etag']);

      }
      if (!empty($last_headers['last-modified'])) {
        $request->addHeader('If-Modified-Since', $last_headers['last-modified']);
      }
      if (!empty($username)) {
        $request->addHeader('Authorization', 'Basic ' . base64_encode("$username:$password"));
      }
    }

    $result = new \stdClass();

    try {
      $response = $request->send();
      $result->data = $response->getBody(TRUE);
      $result->headers = array_change_key_case($response->getHeaders()->toArray());
      $result->code = $response->getStatusCode();
    }
    catch (BadResponseException $e) {
      $response = $e->getResponse();
      watchdog('feeds', 'The feed %url seems to be broken due to "%error".', array('%url' => $url, '%error' => $response->getStatusCode() . ' ' . $response->getReasonPhrase()), WATCHDOG_WARNING);
      drupal_set_message(t('The feed %url seems to be broken because of error "%error".', array('%url' => $url, '%error' => $response->getStatusCode() . ' ' . $response->getReasonPhrase())));
      return FALSE;
    }
    catch (RequestException $e) {
      watchdog('feeds', 'The feed %url seems to be broken due to "%error".', array('%url' => $url, '%error' => $e->getMessage()), WATCHDOG_WARNING);
      drupal_set_message(t('The feed %url seems to be broken because of error "%error".', array('%url' => $url, '%error' => $e->getMessage())));
      return FALSE;
    }

    // In case of 304 Not Modified try to return cached data.
    if ($result->code == 304) {

      if (isset($last_result)) {
        $last_result->from_cache = TRUE;
        return $last_result;
      }
      else {
        // It's a tragedy, this file must exist and contain good data.
        // In this case, clear cache and repeat.
        cache()->delete('feeds_http_download_' . md5($url));
        return static::get($url, $username, $password, $accept_invalid_cert, $request_timeout);
      }
    }

    // Set caches.
    cache()->set('feeds_http_download_' . md5($url), $result);
    $download_cache[$url] = $result;

    return $result;
  }

  /**
   * Returns if the provided $content_type is a feed.
   *
   * @param string $data
   *   The actual data from the http request.
   *
   * @return bool
   *   Returns true if this is a parsable feed, false otherwise.
   */
  public static function isFeed($data) {
    try {
      $feed_type = Reader::detectType($data);
    }
    catch (Exception $e) {
      return FALSE;
    }

    return $feed_type != Reader::TYPE_ANY;
  }

  /**
   * Finds potential feed tags in the HTML document.
   *
   * @param string $html
   *   The html string to search.
   * @param string $url
   *   The url to use as a base url.
   *
   * @return string|bool
   *   The url of the first feed link found, or false if unable to find a link.
   */
  public static function findFeed($html, $url) {
    $use_error = libxml_use_internal_errors(true);
    $entity_loader = libxml_disable_entity_loader(true);
    $dom = new DOMDocument();
    $status = $dom->loadHTML(trim($html));
    libxml_disable_entity_loader($entity_loader);
    libxml_use_internal_errors($use_error);

    if (!$status) {
      return FALSE;
    }

    $feed_set = new FeedSet();
    $feed_set->addLinks($dom->getElementsByTagName('link'), $url);

    // Load the first feed type found.
    foreach (array('atom', 'rss', 'rdf') as $feed_type) {
      if (isset($feed_set->$feed_type)) {
        return $feed_set->$feed_type;
      }
    }

    return FALSE;
  }

}
