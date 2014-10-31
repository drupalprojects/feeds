<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Fetcher\HttpFetcher.
 */

namespace Drupal\feeds\Feeds\Fetcher;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
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
 *   arguments = {"@http_client", "@config.factory", "@cache.default"}
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
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The configuration factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ClientInterface $client, ConfigFactoryInterface $config, CacheBackendInterface $cache) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->client = $client;
    $this->config = $config;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function fetch(FeedInterface $feed, StateInterface $state) {
    $response = $this->get($feed->getSource());

    $feed->setSource($response->getEffectiveUrl());

    // 304, nothing to see here.
    if ($response->getStatusCode() == 304) {
      $state->setMessage($this->t('The feed has not been updated.'));
      throw new EmptyFeedException();
    }

    // Copy the temp stream to a real file.
    $download_file = $this->prepareDirectory($feed->getSource());
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
   *
   * @return \Guzzle\Http\Message\Response
   *   A Guzzle response.
   *
   * @throws \RuntimeException
   *   Thrown if the GET request failed.
   */
  protected function get($url, $cache = TRUE) {
    $url = strtr($url, [
      'feed://' => 'http://',
      'webcal://' => 'http://',
      'feeds://' => 'https://',
      'webcals://' => 'https://',
    ]);

    try {
      $response = $this->client->get($url);
    }
    catch (BadResponseException $e) {
      $response = $e->getResponse();
      $args = ['%url' => $url, '%error' => $response->getStatusCode() . ' ' . $response->getReasonPhrase()];
      throw new \RuntimeException($this->t('The feed %url seems to be broken because of error "%error".', $args));
    }
    catch (RequestException $e) {
      $args = ['%url' => $url, '%error' => $e->getMessage()];
      throw new \RuntimeException($this->t('The feed %url seems to be broken because of error "%error".', $args));
    }

    return $response;
  }

  /**
   * Prepares a destination for file download.
   *
   * @param string $url
   *   The URL to find a spot for.
   *
   * @return string
   *   The filepath of the destination.
   */
  protected function prepareDirectory($url) {
    $download_dir = 'public://feeds_download_cache';
    if ($path = $this->config->get('system.file')->get('path.private')) {
      $download_dir = $path . '/feeds_download_cache';
    }

    file_prepare_directory($download_dir, FILE_MODIFY_PERMISSIONS | FILE_CREATE_DIRECTORY);
    return $download_dir . '/' . md5($url);
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
    return array(
      'auto_detect_feeds' => TRUE,
      'use_pubsubhubbub' => FALSE,
      'fallback_hub' => '',
      'request_timeout' => 30,
    );
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

    $source = $form_state->getValue('source');
    if (!$url = Feed::getCommonSyndication($source, (string) $this->get($source)->getBody())) {
      $form_state->setError($form['source'], $this->t('Invalid feed URL.'));
    }

    $form_state->setValue('source', $url);
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
  public function sourceDefaults() {
    return ['source' => ''];
  }

  /**
   * {@inheritdoc}
   */
  public function onFeedDeleteMultiple(array $feeds) {
    // Remove caches and files for this feeds.
    foreach ($feeds as $feed) {
      $this->cache->delete('feeds_http_download:' . md5($feed->getSource()));

      $cache_file = $this->prepareDirectory($feed->getSource());
      // Don't use file_unmanaged_delete() to avoid useless log messages.
      if (is_file($cache_file)) {
        return drupal_unlink($cache_file);
      }
    }
  }

}
