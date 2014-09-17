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
   * The subscribe queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $subscribeQueue;

  /**
   * The unsubscribe queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $unsubscribeQueue;

  /**
   * The subscription controller.
   *
   * @var \Drupal\feeds\PuSH\SubscriptionInterface
   */
  protected $subscription;

  /**
   * Constructs an UploadFetcher object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Queue\QueueInterface $subscribe_queue
   *   The queue to use for subscriptions.
   * @param \Drupal\Core\Queue\QueueInterface $unsubscribe_queue
   *   The queue to use to unsubscribe.
   * @param \Drupal\feeds\PuSH\SubscriptionInterface $subscription
   *   The subscription controller.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, QueueInterface $subscribe_queue, QueueInterface $unsubscribe_queue, SubscriptionInterface $subscription) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->subscribeQueue = $subscribe_queue;
    $this->unsubscribeQueue = $unsubscribe_queue;
    $this->subscription = $subscription;
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
      $container->get('queue')->get('feeds_push_subscribe'),
      $container->get('queue')->get('feeds_push_unsubscribe'),
      $container->get('feeds.subscription.crud')
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

    $response = $this->get($feed_config['source']);

    // 304, nothing to see here.
    if ($response->getStatusCode() == 304) {
      drupal_set_message($this->t('The feed has not been updated.'));
      return;
    }

    // If there was a redirect, the url will be updated automagically.
    if ($url = $response->getParams()->get('feeds.redirect')) {
      $feed_config['source'] = $url;
      $feed->setConfigFor($this, $feed_config);
    }

    $tempname = $response->getBody()->getUri();
    $response->getBody()->close();

    $download_file = $this->prepareDirectory($feed_config['source']);
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

    $client = \Drupal::httpClient();

    // Add our handy dandy cache plugin. It's magic.
    if ($cache) {
      $client->addSubscriber(new CachePlugin(\Drupal::cache()));
    }

    $request = $client->get($url);

    // Stream to a file to provide the best scenario for intellegent parsers.
    $tempname = drupal_tempnam('temporary://', 'feeds_download_cache_');
    $request->setResponseBody($tempname);

    try {
      $response = $request->send();
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
    $feed_config = $feed->getConfigurationFor($this);
    $url = $feed_config['source'];
    \Drupal::cache()->delete('feeds_http_download:' . md5($url));
    file_unmanaged_delete($this->prepareDirectory($url));
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
    $form['fetcher']['source'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#description' => $this->t('Enter a feed URL.'),
      '#default_value' => isset($feed_config['source']) ? $feed_config['source'] : '',
      '#maxlength' => NULL,
      '#required' => TRUE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateFeedForm(array &$form, FormStateInterface $form_state, FeedInterface $feed) {
    $values =& $form_state['values']['fetcher'];
    $values['source'] = trim($values['source']);

    if (!Feed::validUrl($values['source'], TRUE)) {
      $form_key = 'feeds][' . get_class($this) . '][source';
      form_set_error($form_key, $this->t('The URL %source is invalid.', array('%source' => $values['source'])));
    }
    elseif ($this->configuration['auto_detect_feeds']) {
      $response = $this->get($values['source'], FALSE);
      if ($url = Feed::getCommonSyndication($values['source'], $response->getBody(TRUE))) {
        $values['source'] = $url;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function sourceDefaults() {
    return array('source' => '');
  }

  /**
   * {@inheritdoc}
   *
   * @todo Refactor this like woah.
   */
  public function onFeedSave(FeedInterface $feed, $update) {
    if (!$this->configuration['use_pubsubhubbub']) {
      return;
    }

    $feed_config = $feed->getConfigurationFor($this);

    $item = array(
      'type' => $this->getPluginId(),
      'id' => $feed->id(),
    );

    // Subscription does not exist yet.
    if (!$subscription = $this->subscription->getSubscription($feed->id())) {
      $sub = array(
        'id' => $feed->id(),
        'state' => 'unsubscribed',
        'hub' => '',
        'topic' => $feed_config['source'],
      );
      $this->subscription->setSubscription($sub);
      // Subscribe to new topic.
      $this->subscribeQueue->createItem($item);
    }

    // Source has changed.
    elseif ($subscription['topic'] !== $feed_config['source']) {
      // Subscribe to new topic.
      $this->subscribeQueue->createItem($item);
      // Unsubscribe from old topic.
      $item['data'] = $subscription['topic'];
      $this->unsubscribeQueue->createItem($item);
      // Save new topic to subscription.
      $subscription['topic'] = $feed_config['source'];
      $this->subscription->setSubscription($subscription);
    }

    // Hub exists, but we aren't subscribed.
    // @todo Is this the best way to handle this?
    // @todo Periodically check for new hubs... Always check for new hubs...
    // Maintain a retry count so that we don't keep trying indefinitely.
    elseif ($subscription['hub']) {
      switch ($subscription['state']) {

        // Don't do anything if we are in the process of subscribing.
        case 'subscribe':
        case 'subscribed':
          break;

        default:
          $this->subscribeQueue->createItem($item);
          break;
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * @todo Call sourceDelete() when changing plugins.
   */
  public function onFeedDeleteMultiple(array $feeds) {
    if ($this->configuration['use_pubsubhubbub']) {
      foreach ($feeds as $feed) {
        $item = array(
          'type' => $this->getPluginId(),
          'id' => $feed->id(),
        );
        // Unsubscribe from feed.
        $this->unsubscribeQueue->createItem($item);
      }
    }

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
