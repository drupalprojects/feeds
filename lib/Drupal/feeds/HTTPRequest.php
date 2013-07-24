<?php

/**
 * @file
 * Contains \Drupal\feeds\HTTPRequest.
 *
 * @todo Remove this.
 */

namespace Drupal\feeds;

use Zend\Feed\Reader\FeedSet;
use Zend\Feed\Reader\Reader;

/**
 * Support caching, HTTP Basic Authentication, detection of RSS/Atom feeds,
 * redirects.
 */
class HTTPRequest {

  /**
   * In memory download cache.
   *
   * The download itself is not stored here. Just a pointer to the file.
   *
   * @var array
   */
  protected static $downloadCache = array();

  /**
   * The current URL.
   *
   * @var string
   */
  protected $url;

  protected $settings;

  public function __construct($url, $settings = array()) {
    $this->url = $url;
    $this->settings = $settings += array(
      'accept_invalid_cert' => FALSE,
      'timeout' => variable_get('http_request_timeout', 30),
      'username' => '',
      'password' => '',
    );
  }

  /**
   * Discovers RSS or atom feeds at the given URL.
   *
   * If document in given URL is an HTML document, function attempts to discover
   * RSS or Atom feeds.
   *
   * @return bool|string
   *   The discovered feed, or FALSE if the URL is not reachable or there was an
   *   error.
   */
  public function getCommonSyndication() {

    $download = $this->get();

    // Cannot get the feed, return.
    // static::get() always returns 200 even if its 304.
    if ($download->code != 200) {
      return FALSE;
    }

    $downloaded_string = file_get_contents($download->file);
    // If this happens to be a feed then just return the url.
    if ($this->isFeed($downloaded_string)) {
      return $this->url;
    }

    return $this->findFeed($downloaded_string);
  }

  /**
   * Gets the content from the given URL.
   *
   * @return stdClass
   *   An object that describes the data downloaded from $url.
   */
  public function get() {
    // Support the 'feed' and 'webcal' schemes by converting them into 'http'.
    $url = strtr($this->url, array(
      'feed://' => 'http://',
      'webcal://' => 'http://',
    ));

    // Intra-pagedownload cache, avoid to download the same content twice within
    // one page download (it's possible, compatible and parse calls).
    if (isset(static::$downloadCache[$url])) {
      return static::$downloadCache[$url];
    }

    $username = $this->settings['username'];
    $password = $this->settings['password'];
    if (!$username && valid_url($url, TRUE)) {
      // Handle password protected feeds.
      $url_parts = parse_url($url);
      if (!empty($url_parts['user']) && !empty($url_parts['pass'])) {
        $password = $url_parts['pass'];
        $username = $url_parts['user'];
      }
    }

    $client = \Drupal::httpClient();

    if ($this->settings['accept_invalid_cert']) {
      $client->setSslVerification(FALSE);
    }

    $request = $client->get($url);

    // Stream to file, rather than save in memory. This allows potential parsing
    // of very large files as long as the parser is smart.
    $temp_file = drupal_tempnam('temporary://', 'feeds-download');
    $handle = fopen($temp_file, 'w');
    $request->getCurlOptions()->set(CURLOPT_FILE, $handle);

    // Set auth if found.
    if ($username && $password) {
      $request->setAuth($username, $password, CURLAUTH_ANY);
    }

    // Only download and parse data if really needs refresh.
    // Based on "Last-Modified" and "If-Modified-Since".
    if ($cache = cache()->get('feeds_http_download_' . md5($url))) {
      $last_result = $cache->data;

      if (!empty($last_result->headers['etag'])) {
        $request->addHeader('If-None-Match', $last_result->headers['etag']);

      }
      if (!empty($last_result->headers['last-modified'])) {
        $request->addHeader('If-Modified-Since', $last_result->headers['last-modified']);
      }
    }

    $result = new \stdClass();

    try {
      $response = $request->send();
      $result->headers = array_change_key_case($response->getHeaders()->toArray());
      $result->code = $response->getStatusCode();
      $result->redirect = FALSE;

      // Handle permanent redirects.
      if ($previous_response = $response->getPreviousResponse()) {
        if ($previous_response->getStatusCode() == 301 && $location = $previous_response->getLocation()) {
          $result->redirect = $location;
        }
      }
    }
    catch (BadResponseException $e) {
      $response = $e->getResponse();
      watchdog('feeds', 'The feed %url seems to be broken due to "%error".', array('%url' => $url, '%error' => $response->getStatusCode() . ' ' . $response->getReasonPhrase()), WATCHDOG_WARNING);
      drupal_set_message(t('The feed %url seems to be broken because of error "%error".', array('%url' => $url, '%error' => $response->getStatusCode() . ' ' . $response->getReasonPhrase())));
      fclose($handle);
      return FALSE;
    }
    catch (RequestException $e) {
      watchdog('feeds', 'The feed %url seems to be broken due to "%error".', array('%url' => $url, '%error' => $e->getMessage()), WATCHDOG_WARNING);
      drupal_set_message(t('The feed %url seems to be broken because of error "%error".', array('%url' => $url, '%error' => $e->getMessage())));
      fclose($handle);
      return FALSE;
    }

    fclose($handle);

    // In case of 304 Not Modified try to return cached data.
    if ($result->code == 304) {

      if (isset($last_result) && file_exists($last_result->file)) {
        $last_result->from_cache = TRUE;
        return $last_result;
      }
      else {
        // It's a tragedy, this file must exist and contain good data.
        // In this case, clear cache and repeat.
        cache()->delete('feeds_http_download_' . md5($url));
        return $this->get();
      }
    }

    $download_dir = 'public://feeds_download_cache';
    if (config('system.file')->get('path.private')) {
      $download_dir = 'private://feeds_download_cache';
    }

    file_prepare_directory($download_dir, FILE_MODIFY_PERMISSIONS | FILE_CREATE_DIRECTORY);
    $download_file = $download_dir . '/' . md5($url);
    file_unmanaged_move($temp_file, $download_file, FILE_EXISTS_REPLACE);
    $result->file = $download_file;

    // Set caches.
    cache()->set('feeds_http_download_' . md5($url), $result);
    static::$downloadCache[$url] = $result;

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
  public function isFeed($data) {
    Reader::setExtensionManager(\Drupal::service('feed.bridge.reader'));

    try {
      $feed_type = Reader::detectType($data);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    return $feed_type != Reader::TYPE_ANY;
  }

  /**
   * Finds potential feed tags in the HTML document.
   *
   * @param string $html
   *   The html string to search.
   *
   * @return string|bool
   *   The url of the first feed link found, or false if unable to find a link.
   */
  public function findFeed($html) {
    $use_error = libxml_use_internal_errors(TRUE);
    $entity_loader = libxml_disable_entity_loader(TRUE);

    $dom = new \DOMDocument();
    $status = $dom->loadHTML(trim($html));

    libxml_disable_entity_loader($entity_loader);
    libxml_use_internal_errors($use_error);

    if (!$status) {
      return FALSE;
    }

    $feed_set = new FeedSet();
    $feed_set->addLinks($dom->getElementsByTagName('link'), $this->url);

    // Load the first feed type found.
    foreach (array('atom', 'rss', 'rdf') as $feed_type) {
      if (isset($feed_set->$feed_type)) {
        return $feed_set->$feed_type;
      }
    }

    return FALSE;
  }

}
