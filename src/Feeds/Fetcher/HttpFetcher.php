<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Fetcher\HttpFetcher.
 */

namespace Drupal\feeds\Feeds\Fetcher;

use Drupal\Component\Utility\String;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueInterface;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Exception\NotModifiedException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Guzzle\CachePlugin;
use Drupal\feeds\Plugin\Type\ClearableInterface;
use Drupal\feeds\Plugin\Type\ConfigurablePluginBase;
use Drupal\feeds\Plugin\Type\FeedPluginFormInterface;
use Drupal\feeds\Plugin\Type\Fetcher\FetcherInterface;
use Drupal\feeds\PuSH\SubscriptionInterface;
use Drupal\feeds\RawFetcherResult;
use Drupal\feeds\Result\HttpFetcherResult;
use Drupal\feeds\StateInterface;
use Drupal\feeds\Utility\Feed;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Stream\Stream;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines an HTTP fetcher.
 *
 * @Plugin(
 *   id = "http",
 *   title = @Translation("Download"),
 *   description = @Translation("Downloads data from a URL using Drupal's HTTP request handler.")
 * )
 */
class HttpFetcher extends ConfigurablePluginBase implements FetcherInterface, ClearableInterface, FeedPluginFormInterface, ContainerFactoryPluginInterface {

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
   * @todo Merge the two queues.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('config.factory'),
      $container->get('cache.default')
    );
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
    $url = strtr($url, ['feed://' => 'http://', 'webcal://' => 'http://']);

    // Add our handy dandy cache plugin. It's magic.
    // if ($cache) {
    //   $this->client->addSubscriber(new CachePlugin(\Drupal::cache()));
    // }

    try {
      $response = $this->client->get($url, [
        'save_to' => drupal_tempnam('temporary://', 'feeds_download_cache_'),
      ]);
    }
    catch (BadResponseException $e) {
      $response = $e->getResponse();
      $args = array('%url' => $url, '%error' => $response->getStatusCode() . ' ' . $response->getReasonPhrase());
      watchdog('feeds', 'The feed %url seems to be broken due to "%error".', $args, WATCHDOG_ERROR);
      throw new \RuntimeException($this->t('The feed %url seems to be broken because of error "%error".', $args));
    }
    catch (RequestException $e) {
      $args = array('%url' => $url, '%error' => $e->getMessage());
      watchdog('feeds', 'The feed %url seems to be broken due to "%error".', $args, WATCHDOG_ERROR);
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
    file_unmanaged_delete($this->prepareDirectory($feed->getSource()));
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'auto_detect_feeds' => TRUE,
      'use_pubsubhubbub' => FALSE,
      'designated_hub' => '',
      'request_timeout' => 30,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['auto_detect_feeds'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Auto detect feeds'),
      '#description' => $this->t('If the supplied URL does not point to a feed but an HTML document, attempt to extract a feed URL from the document.'),
      '#default_value' => $this->configuration['auto_detect_feeds'],
    );
    $form['use_pubsubhubbub'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Use PubSubHubbub'),
      '#description' => $this->t('Attempt to use a <a href="http://en.wikipedia.org/wiki/PubSubHubbub">PubSubHubbub</a> subscription if available.'),
      '#default_value' => $this->configuration['use_pubsubhubbub'],
    );
    $form['designated_hub'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Designated hub'),
      '#description' => $this->t('Enter the URL of a designated PubSubHubbub hub (e. g. superfeedr.com). If given, this hub will be used instead of the hub specified in the actual feed.'),
      '#default_value' => $this->configuration['designated_hub'],
      '#states' => array(
        'visible' => array(
          'input[name="fetcher[configuration][use_pubsubhubbub]"]' => array('checked' => TRUE),
        ),
      ),
    );
    // Per importer override of global http request timeout setting.
    $form['request_timeout'] = array(
      '#type' => 'number',
      '#title' => $this->t('Request timeout'),
      '#description' => $this->t('Timeout in seconds to wait for an HTTP request to finish.'),
      '#default_value' => $this->configuration['request_timeout'],
      '#min' => 0,
    );

    return $form;
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
    return array('source' => '', 'thing' => '');
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
