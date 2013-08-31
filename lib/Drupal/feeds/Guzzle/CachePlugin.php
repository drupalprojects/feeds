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
   * The cached response.
   *
   * @var \stdClass
   */
  protected $cache;

  /**
   * Constructs a CachePlugin object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend to use.
   */
  public function __construct(CacheBackendInterface $cache_backend) {
    $this->cacheBackend = $cache_backend;
  }

  public function onRequestBeforeSend(Event $event) {
    $request = $event['request'];

    if ($request->getMethod() != RequestInterface::GET) {
      return;
    }

    $cid = $this->getCacheKey($request->getUrl());

    if ($cache = $this->cacheBackend->get($cid)) {

      if (!empty($cache->data->headers['etag'])) {
        $request->addHeader('If-None-Match', $cache->data->headers['etag']);

      }
      if (!empty($cache->data->headers['last-modified'])) {
        $request->addHeader('If-Modified-Since', $cache->data->headers['last-modified']);
      }
    }

  }

  public function onRequestSent(Event $event) {
    $request = $event['request'];
    $response = $event['response'];

    $url = $request->getUrl();
    // Handle permanent redirects by setting the redirected URL.
    $redirect = FALSE;
    if ($previous_response = $response->getPreviousResponse()) {
      if ($previous_response->getStatusCode() == 301 && $location = $previous_response->getLocation()) {
        $response->getParams()->set('feeds.redirect', $location);
        $redirect = TRUE;
        $url = $location;
      }
    }

    // If we were redirected, we want to create a new cache entry. Otherwise,
    // peace.
    if (!$redirect && $response->getStatusCode() == 304) {
      return;
    }

    if ($redirect) {
      $this->cacheBackend->delete($this->getCacheKey($request->getUrl()));
    }

    $cache = new \stdClass();
    $cache->headers = array_change_key_case($response->getHeaders()->toArray());
    // @todo We should only cache for certain status codes.
    $cache->code = $response->getStatusCode();

    $cid = $this->getCacheKey($url);
    $this->cacheBackend->set($cid, $cache);
  }

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
