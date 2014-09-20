<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Fetcher\HttpFetcher.
 */

namespace Drupal\feeds\Feeds\Fetcher;

use Drupal\Component\Utility\String;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueInterface;
use Drupal\feeds\Exception\NotModifiedException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Guzzle\CachePlugin;
use Drupal\feeds\Plugin\Type\ClearableInterface;
use Drupal\feeds\Plugin\Type\ConfigurablePluginBase;
use Drupal\feeds\Plugin\Type\FeedPluginFormInterface;
use Drupal\feeds\Plugin\Type\Fetcher\FetcherInterface;
use Drupal\feeds\PuSH\PuSHFetcherInterface;
use Drupal\feeds\PuSH\SubscriptionInterface;
use Drupal\feeds\RawFetcherResult;
use Drupal\feeds\Result\FetcherResult;
use Drupal\feeds\Utility\Feed;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Stream\Stream;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines an HTTP fetcher.
 *
 * @todo Make a new subscriber interface.
 *
 * @Plugin(
 *   id = "http",
 *   title = @Translation("Download"),
 *   description = @Translation("Downloads data from a URL using Drupal's HTTP request handler.")
 * )
 */
class HttpFetcher extends ConfigurablePluginBase implements FetcherInterface, ClearableInterface, PuSHFetcherInterface, FeedPluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * The Guzzle client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * Constructs an UploadFetcher object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ClientInterface $client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->client = $client;
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
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @todo Make parsers be able to handle streams. Maybe exclusively.
   * @todo Clean download cache directory.
   */
  public function fetch(FeedInterface $feed) {
    $feed_config = $feed->getConfigurationFor($this);

    $response = $this->get($feed->getSource());

    // 304, nothing to see here.
    if ($response->getStatusCode() == 304) {
      drupal_set_message($this->t('The feed has not been updated.'));
      return;
    }

    // If there was a redirect, the url will be updated automagically.
    // if ($url = $response->getParams()->get('feeds.redirect')) {
    //   $feed_config['source'] = $url;
    //   $feed->setConfigFor($this, $feed_config);
    // }

    $tempname = $response->getBody()->getMetadata('uri');
    $response->getBody()->close();

    $download_file = $this->prepareDirectory($feed->getSource());
    file_unmanaged_move($tempname, $download_file, FILE_EXISTS_REPLACE);

    return new FetcherResult($download_file);
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
    $url = strtr($url, array(
      'feed://' => 'http://',
      'webcal://' => 'http://',
    ));

    // Add our handy dandy cache plugin. It's magic.
    // if ($cache) {
    //   $this->client->addSubscriber(new CachePlugin(\Drupal::cache()));
    // }

    $request = $this->client->createRequest('GET', $url, array(
      'save_to' => drupal_tempnam('temporary://', 'feeds_download_cache_'),
    ));

    try {
      $response = $this->client->send($request);
    }
    catch (BadResponseException $e) {
      $response = $e->getResponse();
      $args = array('%url' => $url, '%error' => $response->getStatusCode() . ' ' . $response->getReasonPhrase());
      watchdog('feeds', 'The feed %url seems to be broken due to "%error".', $args, WATCHDOG_WARNING);
      drupal_set_message($this->t('The feed %url seems to be broken because of error "%error".', $args, 'warning'));
      return FALSE;
    }
    catch (RequestException $e) {
      $args = array('%url' => $url, '%error' => $e->getMessage());
      watchdog('feeds', 'The feed %url seems to be broken due to "%error".', $args, WATCHDOG_WARNING);
      drupal_set_message($this->t('The feed %url seems to be broken because of error "%error".', $args), 'warning');
      return FALSE;
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
    if (\Drupal::config('system.file')->get('path.private')) {
      $download_dir = 'private://feeds_download_cache';
    }

    file_prepare_directory($download_dir, FILE_MODIFY_PERMISSIONS | FILE_CREATE_DIRECTORY);

    return $download_dir . '/' . md5($url);
  }

  /**
   * {@inheritdoc}
   */
  public function push(FeedInterface $feed, $raw) {
    // Handle pubsubhubbub.
    if ($this->configuration['use_pubsubhubbub']) {
      return new RawFetcherResult($raw);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clear(FeedInterface $feed) {
    \Drupal::cache()->delete('feeds_http_download:' . md5($feed->getSource()));
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
    $feed_config = $feed->getConfigurationFor($this);

    $form['fetcher']['#tree'] = TRUE;
    $form['fetcher']['thing'] = array(
      '#title' => 'tasdf',
      '#type' => 'textfield',
      '#default_value' => $feed_config['thing'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateFeedForm(array &$form, FormStateInterface $form_state, FeedInterface $feed) {
    $values =& $form_state->getValue('fetcher');

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

  /**
   * {@inheritdoc}
   */
  public function importPeriod(FeedInterface $feed) {
    $sub = $this->subscription->getSubscription($feed->id());
    if ($sub && $sub['state'] == 'subscribed') {
      // Delay for three days if there is a successful subscription.
      return 259200;
    }
  }

}
