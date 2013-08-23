<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Fetcher\HTTPFetcher.
 */

namespace Drupal\feeds\Plugin\feeds\Fetcher;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\feeds\Exception\NotModifiedException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedPluginFormInterface;
use Drupal\feeds\Result\FetcherResult;
use Drupal\feeds\Utility\HTTPRequest;
use Drupal\feeds\Plugin\ConfigurablePluginBase;
use Drupal\feeds\Plugin\ClearableInterface;
use Drupal\feeds\Plugin\FetcherInterface;
use Drupal\feeds\PuSH\PuSHFetcherInterface;
use Drupal\feeds\RawFetcherResult;

/**
 * Defines an HTTP fetcher.
 *
 * @todo Make a new subscriber interface.
 *
 * @Plugin(
 *   id = "http",
 *   title = @Translation("HTTP fetcher"),
 *   description = @Translation("Downloads data from a URL using Drupal's HTTP request handler.")
 * )
 */
class HTTPFetcher extends ConfigurablePluginBase implements FeedPluginFormInterface, FetcherInterface, ClearableInterface, PuSHFetcherInterface {

  /**
   * {@inheritdoc}
   */
  public function fetch(FeedInterface $feed) {
    $feed_config = $feed->getConfigurationFor($this);

    $http = new HTTPRequest($feed_config['source'], array('timeout' => $this->configuration['request_timeout']));
    $result = $http->get();
    if (!in_array($result->code, array(200, 201, 202, 203, 204, 205, 206))) {
      throw new \Exception(t('Download of @url failed with code !code.', array('@url' => $feed_config['source'], '!code' => $result->code)));
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
      '#title' => t('Auto detect feeds'),
      '#description' => t('If the supplied URL does not point to a feed but an HTML document, attempt to extract a feed URL from the document.'),
      '#default_value' => $this->configuration['auto_detect_feeds'],
    );
    $form['use_pubsubhubbub'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use PubSubHubbub'),
      '#description' => t('Attempt to use a <a href="http://en.wikipedia.org/wiki/PubSubHubbub">PubSubHubbub</a> subscription if available.'),
      '#default_value' => $this->configuration['use_pubsubhubbub'],
    );
    $form['designated_hub'] = array(
      '#type' => 'textfield',
      '#title' => t('Designated hub'),
      '#description' => t('Enter the URL of a designated PubSubHubbub hub (e. g. superfeedr.com). If given, this hub will be used instead of the hub specified in the actual feed.'),
      '#default_value' => $this->configuration['designated_hub'],
      '#dependency' => array(
        'edit-use-pubsubhubbub' => array(1),
      ),
    );
    // Per importer override of global http request timeout setting.
    $form['request_timeout'] = array(
      '#type' => 'number',
      '#title' => t('Request timeout'),
      '#description' => t('Timeout in seconds to wait for an HTTP get request to finish.</br>
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
      '#title' => t('URL'),
      '#description' => t('Enter a feed URL.'),
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

    if (!feeds_valid_url($values['source'], TRUE)) {
      $form_key = 'feeds][' . get_class($this) . '][source';
      form_set_error($form_key, t('The URL %source is invalid.', array('%source' => $values['source'])));
    }
    elseif ($this->configuration['auto_detect_feeds']) {
      $http = new HTTPRequest($values['source']);
      if ($url = $http->getCommonSyndication()) {
        $values['source'] = $url;
      }
    }
  }

  /**
   * {@inheritdoc}
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
    if (!$subscription = \Drupal::service('feeds.subscription.crud')->getSubscription($feed->id())) {
      $sub = array(
        'id' => $feed->id(),
        'state' => 'unsubscribed',
        'hub' => '',
        'topic' => $feed_config['source'],
      );

      \Drupal::service('feeds.subscription.crud')->setSubscription($sub);

      // Subscribe to new topic.
      \Drupal::queue('feeds_push_subscribe')->createItem($item);
    }

    // Source has changed.
    elseif ($subscription['topic'] !== $feed_config['source']) {
      // Subscribe to new topic.
      \Drupal::queue('feeds_push_subscribe')->createItem($item);

      // Unsubscribe from old topic.
      $item['data'] = $subscription['topic'];
      \Drupal::queue('feeds_push_unsubscribe')->createItem($item);

      // Save new topic to subscription.
      $subscription['topic'] = $feed_config['source'];
      \Drupal::service('feeds.subscription.crud')->setSubscription($subscription);
    }

    // Hub exists, but we aren't subscribed.
    // @todo Is this the best way to handle this?
    // @todo Periodically check for new hubs... Always check for new hubs...
    // Maintain a retry count so that we don't keep trying indefinitely.
    elseif ($subscription['hub']) {
      switch ($subscription['state']) {
        case 'subscribe':
        case 'subscribed':
          break;

        default:
          \Drupal::queue('feeds_push_subscribe')->createItem($item);
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
      \Drupal::queue('feeds_push_unsubscribe')->createItem($item);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function importPeriod(FeedInterface $feed) {
    $sub = \Drupal::service('feeds.subscription.crud')->getSubscription($feed->id());
    if ($sub && $sub['state'] == 'subscribed') {
      // Delay for three days if there is a successful subscription.
      return 259200;
    }
  }

}
