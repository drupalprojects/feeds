<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Fetcher\HttpFetcher.
 */

namespace Drupal\feeds\Feeds\Fetcher;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\ClearableInterface;
use Drupal\feeds\Plugin\Type\FeedPluginFormInterface;
use Drupal\feeds\Plugin\Type\Fetcher\FetcherInterface;
use Drupal\feeds\Plugin\Type\PluginBase;
use Drupal\feeds\Result\HttpFetcherResult;
use Drupal\feeds\StateInterface;
use Drupal\feeds\Utility\Feed;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Stream\Utils;

/**
 * Defines an HTTP fetcher.
 *
 * @FeedsFetcher(
 *   id = "http",
 *   title = @Translation("Download"),
 *   description = @Translation("Downloads data from a URL using Drupal's HTTP request handler."),
 *   configuration_form = "Drupal\feeds\Feeds\Fetcher\Form\HttpFetcherForm",
 *   arguments = {"@http_client", "@cache.feeds_download"}
 * )
 */
class HttpFetcher extends PluginBase implements ClearableInterface, FeedPluginFormInterface, FetcherInterface {

  /**
   * The Guzzle client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructs an UploadFetcher object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \GuzzleHttp\ClientInterface $client
   *   The Guzzle client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ClientInterface $client, CacheBackendInterface $cache) {
    $this->client = $client;
    $this->cache = $cache;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function fetch(FeedInterface $feed, StateInterface $state) {
    $response = $this->get($feed->getSource(), $this->getCacheKey($feed));
    $feed->setSource($response->getEffectiveUrl());

    // 304, nothing to see here.
    if ($response->getStatusCode() == 304) {
      $state->setMessage($this->t('The feed has not been updated.'));
      throw new EmptyFeedException();
    }

    // Copy the temp stream to a real file.
    $download_file = drupal_tempnam('temporary://', 'feeds_http_fetcher');
    $dest_stream = Utils::create(fopen($download_file, 'w+'));
    Utils::copyToStream($response->getBody(), $dest_stream);
    $response->getBody()->close();
    $dest_stream->close();

    return new HttpFetcherResult($download_file, $response->getHeaders());
  }

  /**
   * Performs a GET request.
   *
   * @param string $url
   *   The URL to GET.
   * @param string $cache_key
   *   (optional) The cache key to find cached headers. Defaults to false.
   *
   * @return \Guzzle\Http\Message\Response
   *   A Guzzle response.
   *
   * @throws \RuntimeException
   *   Thrown if the GET request failed.
   */
  protected function get($url, $cache_key = FALSE) {
    $url = strtr($url, [
      'feed://' => 'http://',
      'webcal://' => 'http://',
      'feeds://' => 'https://',
      'webcals://' => 'https://',
    ]);

    // Add cached headers if requested.
    $headers = [];
    if ($cache_key && ($cache = $this->cache->get($cache_key))) {
      if (isset($cache->data['etag'])) {
        $headers['If-None-Match'] = $cache->data['etag'];
      }
      if (isset($cache->data['last-modified'])) {
        $headers['If-Modified-Since'] = $cache->data['last-modified'];
      }
    }

    try {
      $response = $this->client->get($url, ['headers' => $headers]);
    }
    catch (BadResponseException $e) {
      $response = $e->getResponse();
      $args = [
        '%url' => $url,
        '%error' => $response->getStatusCode() . ' ' . $response->getReasonPhrase(),
      ];
      throw new \RuntimeException($this->t('The feed %url seems to be broken because of error "%error".', $args));
    }
    catch (RequestException $e) {
      $args = ['%url' => $url, '%error' => $e->getMessage()];
      throw new \RuntimeException($this->t('The feed %url seems to be broken because of error "%error".', $args));
    }

    if ($cache_key) {
      $this->cache->set($cache_key, array_change_key_case($response->getHeaders()));
    }

    return $response;
  }

  /**
   * Returns the download cache key for a given feed.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to find the cache key for.
   *
   * @return string
   *   The cache key for the feed.
   */
  protected function getCacheKey(FeedInterface $feed) {
    return $feed->id() . ':' . md5($feed->getSource());
  }

  /**
   * {@inheritdoc}
   */
  public function clear(FeedInterface $feed, StateInterface $state) {
    $this->onFeedDeleteMultiple([$feed]);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'auto_detect_feeds' => TRUE,
      'use_pubsubhubbub' => FALSE,
      'fallback_hub' => '',
      'request_timeout' => 30,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildFeedForm(array $form, FormStateInterface $form_state, FeedInterface $feed) {
    $form['source'] = [
      '#title' => $this->t('Feed URL'),
      '#type' => 'url',
      '#default_value' => $feed->getSource(),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateFeedForm(array &$form, FormStateInterface $form_state, FeedInterface $feed) {
    if (!$this->configuration['auto_detect_feeds']) {
      return;
    }

    $response = $this->get($form_state->getValue('source'));
    if ($url = Feed::getCommonSyndication($response->getEffectiveUrl(), (string) $response->getBody())) {
      $form_state->setValue('source', $url);
    }
    else {
      $form_state->setError($form['source'], $this->t('Invalid feed URL.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitFeedForm(array &$form, FormStateInterface $form_state, FeedInterface $feed) {
    $feed->setSource($form_state->getValue('source'));
  }

  /**
   * {@inheritdoc}
   */
  public function onFeedDeleteMultiple(array $feeds) {
    foreach ($feeds as $feed) {
      $this->cache->delete($this->getCacheKey($feed));
    }
  }

}
