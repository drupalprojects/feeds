<?php

/**
 * @file
 * Contains \Drupal\feeds\Guzzle\CachePlugin.
 */

namespace Drupal\feeds\Guzzle;

use Drupal\Core\Cache\CacheBackendInterface;
use Guzzle\Common\Event;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Exception\CurlException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A simple caching strategy for feeds.
 */
class CachePlugin implements EventSubscriberInterface {

  /**
   * In memory download cache.
   *
   * The download itself is not stored here. Just a pointer to the file.
   *
   * @var array
   */
  protected static $downloadCache = array();

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * Constructs a CachePlugin object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend to use.
   */
  public function __construct(CacheBackendInterface $cache_backend) {
    $this->cacheBackend = $cache_backend;
  }

  /**
   * Intervenes before a request starts to add cache headers.
   *
   * @param \Guzzle\Common\Event $event
   *   The Guzzle event object.
   */
  public function onRequestBeforeSend(Event $event) {
    $request = $event['request'];

    // We're only handling GET requests for now. That's all we do anyway.
    if ($request->getMethod() != RequestInterface::GET) {
      return;
    }

    $url = $request->getUrl();

    // In-memory download cache. Sometimes we fetch the same URL more than
    // once in a page load.
    // @todo Be smarter.
    if (isset(static::$downloadCache[$url])) {
      $request->setResponse(static::$downloadCache[$url]);
      return;
    }

    if ($cache = $this->cacheBackend->get($this->getCacheKey($url))) {

      // Add any headers that could be useful.
      // @todo Look at Guzzle's own cache plugin, or add a smarter cache here.
      if (!empty($cache->data->headers['etag'])) {
        $request->addHeader('If-None-Match', $cache->data->headers['etag']);
      }
      if (!empty($cache->data->headers['last-modified'])) {
        $request->addHeader('If-Modified-Since', $cache->data->headers['last-modified']);
      }
    }
  }

  /**
   * Responds after a request has finished, but before it is sent to the client.
   *
   * @param \Guzzle\Common\Event $event
   *   The Guzzle event object.
   */
  public function onRequestSent(Event $event) {
    $request = $event['request'];
    $response = $event['response'];

    // Handle permanent redirects by setting the redirected URL so that the
    // client can grab it quickly.
    $redirect = FALSE;
    $url = $old_url = $request->getUrl();
    if ($previous_response = $response->getPreviousResponse()) {
      if ($previous_response->getStatusCode() == 301 && $location = $previous_response->getLocation()) {
        $response->getParams()->set('feeds.redirect', $location);
        $redirect = TRUE;
        $url = $request->getUrl();
      }
    }

    $cache_hit = $response->getStatusCode() == 304;

    if ($redirect) {
      // Delete the old cache entry.
      $this->cacheBackend->delete($this->getCacheKey($old_url));
      // Not sure if the repeated requests are smart enough to find the
      // redirect, so cache the old URL with the new response.
      static::$downloadCache[$old_url] = $response;
    }

    if ($redirect || !$cache_hit) {
      $cache = new \stdClass();
      $cache->headers = array_change_key_case($response->getHeaders()->toArray());
      // @todo We should only cache for certain status codes.
      $cache->code = $response->getStatusCode();

      $this->cacheBackend->set($this->getCacheKey($url), $cache);
    }

    // Set in-page download cache.
    static::$downloadCache[$url] = $response;
  }

  /**
   * Returns the cache key for a give URL.
   *
   * @param string $url
   *   The URL.
   *
   * @return string
   *   The cache key for the given URL.
   */
  protected function getCacheKey($url) {
    return 'feeds_http_download:' . md5($url);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return array(
      'request.before_send' => array('onRequestBeforeSend', -255),
      'request.sent' => array('onRequestSent', 255),
    );
  }

}
