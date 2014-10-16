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
use Drupal\feeds\Plugin\Type\Fetcher\FetcherInterface;
use Drupal\feeds\Plugin\Type\PluginBase;
use Drupal\feeds\Result\HttpFetcherResult;
use Drupal\feeds\StateInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;

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
class HttpFetcher extends PluginBase implements FetcherInterface, ClearableInterface {

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
   *
   * @todo Make parsers be able to handle streams. Maybe exclusively.
   * @todo Clean download cache directory.
   */
  public function fetch(FeedInterface $feed) {
    $response = $this->get($feed->getSource());

    // 304, nothing to see here.
    if ($response->getStatusCode() == 304) {
      $feed->getState(StateInterface::FETCH)->setMessage($this->t('The feed has not been updated.'));
      throw new EmptyFeedException();
    }

    $tempname = $response->getBody()->getMetadata('uri');
    $response->getBody()->close();

    $download_file = $this->prepareDirectory($feed->getSource());
    file_unmanaged_move($tempname, $download_file, FILE_EXISTS_REPLACE);

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
   */
  protected function get($url, $cache = TRUE) {
    $url = strtr($url, [
      'feed://' => 'http://',
      'webcal://' => 'http://',
      'feeds://' => 'https://',
      'webcals://' => 'https://',
    ]);

    try {
      $response = $this->client->get($url, [
        'save_to' => drupal_tempnam('temporary://', 'feeds_download_cache_'),
      ]);
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
  public function clear(FeedInterface $feed) {
    $this->cache->delete('feeds_http_download:' . md5($feed->getSource()));

    $cache_file = $this->prepareDirectory($feed->getSource());
    if (file_exists($cache_file)) {
      file_unmanaged_delete($cache_file);
    }
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
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateFeedForm(array &$form, FormStateInterface $form_state, FeedInterface $feed) {
    // $values =& $form_state->getValue('fetcher');

    // if ($this->configuration['auto_detect_feeds']) {
    //   $response = $this->get($values['source'], FALSE);
    //   if ($url = Feed::getCommonSyndication($values['source'], $response->getBody(TRUE))) {
    //     $values['source'] = $url;
    //   }
    // }
  }

  /**
   * {@inheritdoc}
   */
  public function sourceDefaults() {
    return ['source' => ''];
  }

  /**
   * {@inheritdoc}
   *
   * @todo Call sourceDelete() when changing plugins.
   */
  public function onFeedDeleteMultiple(array $feeds) {
    // Remove caches and files for this feeds.
    foreach ($feeds as $feed) {
      $this->clear($feed);
    }
  }

}
