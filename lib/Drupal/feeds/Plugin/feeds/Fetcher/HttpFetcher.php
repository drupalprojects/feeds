<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Fetcher\HttpFetcher.
 */

namespace Drupal\feeds\Plugin\feeds\Fetcher;

use Drupal\Component\Annotation\Plugin;
use Drupal\Component\Utility\String;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\feeds\Exception\NotModifiedException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedPluginFormInterface;
use Drupal\feeds\Result\FetcherResult;
use Drupal\feeds\Utility\HttpRequest;
use Drupal\feeds\Plugin\ConfigurablePluginBase;
use Drupal\feeds\Plugin\ClearableInterface;
use Drupal\feeds\Plugin\FetcherInterface;
use Drupal\feeds\PuSH\PuSHFetcherInterface;
use Drupal\feeds\PuSH\SubscriptionInterface;
use Drupal\feeds\RawFetcherResult;
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
class HttpFetcher extends ConfigurablePluginBase implements FeedPluginFormInterface, FetcherInterface, ClearableInterface, PuSHFetcherInterface, ContainerFactoryPluginInterface {

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
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
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
   */
  public function fetch(FeedInterface $feed) {
    $feed_config = $feed->getConfigurationFor($this);

    $http = new HttpRequest($feed_config['source'], array('timeout' => $this->configuration['request_timeout']));
    $result = $http->get();
    if (!in_array($result->code, array(200, 201, 202, 203, 204, 205, 206))) {
      throw new \Exception(String::format('Download of @url failed with code !code.', array('@url' => $feed_config['source'], '!code' => $result->code)));
    }
    // Update source if there was a permanent redirect.
    if ($result->redirect) {
      $feed_config['source'] = $result->redirect;
      $feed->setConfigurationFor($this, $feed_config);
    }
    if ($result->code == 304) {
      throw new NotModifiedException();
    }
    return new FetcherResult($result->file);
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
    cache()->delete('feeds_http_download_' . md5($url));
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultConfiguration() {
    return array(
      'auto_detect_feeds' => FALSE,
      'use_pubsubhubbub' => FALSE,
      'designated_hub' => '',
      'request_timeout' => NULL,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
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
      '#dependency' => array(
        'edit-use-pubsubhubbub' => array(1),
      ),
    );
    // Per importer override of global http request timeout setting.
    $form['request_timeout'] = array(
      '#type' => 'number',
      '#title' => $this->t('Request timeout'),
      '#description' => $this->t('Timeout in seconds to wait for an HTTP get request to finish.</br>
                         <b>Note:</b> this setting will override the global setting.</br>
                         When left empty, the global value is used.'),
      '#default_value' => $this->configuration['request_timeout'],
      '#min' => 0,
      '#maxlength' => 3,
      '#size' => 30,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildFeedForm(array $form, array &$form_state, FeedInterface $feed) {
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
  public function validateFeedForm(array &$form, array &$form_state, FeedInterface $feed) {
    $values =& $form_state['values']['fetcher'];
    $values['source'] = trim($values['source']);

    if (!HttpRequest::validUrl($values['source'], TRUE)) {
      $form_key = 'feeds][' . get_class($this) . '][source';
      form_set_error($form_key, $this->t('The URL %source is invalid.', array('%source' => $values['source'])));
    }
    elseif ($this->configuration['auto_detect_feeds']) {
      $http = new HttpRequest($values['source']);
      if ($url = $http->getCommonSyndication()) {
        $values['source'] = $url;
      }
    }
  }

  public function sourceDefaults() {
    return array('source' => '');
  }

  /**
   * {@inheritdoc}
   *
   * @todo Refactor this like woah.
   */
  public function sourceSave(FeedInterface $feed) {
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
   * @todo Clear cache when deleting.
   */
  public function sourceDelete(FeedInterface $feed) {
    if ($this->configuration['use_pubsubhubbub']) {
      $item = array(
        'type' => $this->getPluginId(),
        'id' => $feed->id(),
      );
      // Unsubscribe from feed.
      $this->unsubscribeQueue->createItem($item);
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
