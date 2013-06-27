<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Fetcher\HTTPFetcher.
 */

namespace Drupal\feeds\Plugin\feeds\Fetcher;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedPluginFormInterface;
use Drupal\feeds\Plugin\FetcherBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\feeds\PuSHSubscriber;
use Drupal\feeds\PuSHEnvironment;
use Drupal\feeds\HTTPFetcherResult;
use Drupal\feeds\HTTPRequest;

/**
 * Defines an HTTP fetcher.
 *
 * Uses http_request_get() to download a feed.
 *
 * @Plugin(
 *   id = "http",
 *   title = @Translation("HTTP fetcher"),
 *   description = @Translation("Downloads data from a URL using Drupal's HTTP request handler.")
 * )
 */
class HTTPFetcher extends FetcherBase implements FeedPluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function fetch(FeedInterface $feed) {
    $feed_config = $feed->getConfigFor($this);
    if ($this->config['use_pubsubhubbub'] && ($raw = $this->subscriber($feed->id())->receive())) {
      return new FeedsFetcherResult($raw);
    }
    $fetcher_result = new HTTPFetcherResult($feed_config['source']);
    // When request_timeout is empty, the global value is used.
    $fetcher_result->setTimeout($this->config['request_timeout']);
    return $fetcher_result;
  }

  /**
   * {@inheritdoc}
   */
  public function clear(FeedInterface $feed) {
    $feed_config = $feed->getConfigFor($this);
    $url = $feed_config['source'];
    cache()->delete('feeds_http_download_' . md5($url));
  }

  /**
   * {@inheritdoc}
   */
  public function request($fid = 0) {
    // A subscription verification has been sent, verify.
    if (isset($_GET['hub_challenge'])) {
      $this->subscriber($fid)->verifyRequest();
    }
    // No subscription notification has ben sent, we are being notified.
    else {
      try {
        entity_load('feeds_feed', $fid)->existing()->import();
      }
      catch (Exception $e) {
        // In case of an error, respond with a 503 Service (temporary)
        // unavailable.
        header('HTTP/1.1 503 "Not Found"', NULL, 503);
        drupal_exit();
      }
    }
    // Will generate the default 200 response.
    header('HTTP/1.1 200 "OK"', NULL, 200);
    drupal_exit();
  }

  /**
   * {@inheritdoc}
   */
  public function configDefaults() {
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
  public function configForm(array $form, array &$form_state) {
    $form['auto_detect_feeds'] = array(
      '#type' => 'checkbox',
      '#title' => t('Auto detect feeds'),
      '#description' => t('If the supplied URL does not point to a feed but an HTML document, attempt to extract a feed URL from the document.'),
      '#default_value' => $this->config['auto_detect_feeds'],
    );
    $form['use_pubsubhubbub'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use PubSubHubbub'),
      '#description' => t('Attempt to use a <a href="http://en.wikipedia.org/wiki/PubSubHubbub">PubSubHubbub</a> subscription if available.'),
      '#default_value' => $this->config['use_pubsubhubbub'],
    );
    $form['designated_hub'] = array(
      '#type' => 'textfield',
      '#title' => t('Designated hub'),
      '#description' => t('Enter the URL of a designated PubSubHubbub hub (e. g. superfeedr.com). If given, this hub will be used instead of the hub specified in the actual feed.'),
      '#default_value' => $this->config['designated_hub'],
      '#dependency' => array(
        'edit-use-pubsubhubbub' => array(1),
      ),
    );
    // Per importer override of global http request timeout setting.
    $form['request_timeout'] = array(
      '#type' => 'number',
      '#title' => t('Request timeout'),
      '#description' => t('Timeout in seconds to wait for an HTTP get request to finish.</br>' .
                         '<b>Note:</b> this setting will override the global setting.</br>' .
                         'When left empty, the global value is used.'),
      '#default_value' => $this->config['request_timeout'],
      '#min' => 0,
      '#maxlength' => 3,
      '#size' => 30,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function feedForm(array $form, array &$form_state, FeedInterface $feed) {
    $feed_config = $feed->getConfigFor($this);

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
  public function feedFormValidate(array $form, array &$form_state, FeedInterface $feed) {
    $values =& $form_state['values']['fetcher'];
    $values['source'] = trim($values['source']);

    if (!feeds_valid_url($values['source'], TRUE)) {
      $form_key = 'feeds][' . get_class($this) . '][source';
      form_set_error($form_key, t('The URL %source is invalid.', array('%source' => $values['source'])));
    }
    elseif ($this->config['auto_detect_feeds']) {
      if ($url = HTTPRequest::getCommonSyndication($values['source'])) {
        $values['source'] = $url;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function sourceSave(FeedInterface $feed) {
    if ($this->config['use_pubsubhubbub']) {
      $job = array(
        'fetcher' => $this,
        'source' => $feed,
      );
      feeds_set_subscription_job($job);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function sourceDelete(FeedInterface $feed) {
    if ($this->config['use_pubsubhubbub']) {
      $job = array(
        'type' => $feed->getImporter()->id(),
        'id' => $feed->id(),
        'period' => 0,
        'periodic' => FALSE,
      );
      JobScheduler::get('feeds_push_unsubscribe')->set($job);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function subscribe(FeedInterface $feed) {
    $feed_config = $feed->getConfigFor($this);
    $sub = $this->subscriber($feed->id());
    $url = valid_url($this->config['designated_hub']) ? $this->config['designated_hub'] : '';
    $path = url($this->path($feed->id()), array('absolute' => TRUE));
    $sub->subscribe($feed_config['source'], $path, $url);
  }

  /**
   * {@inheritdoc}
   */
  public function unsubscribe(FeedInterface $feed) {
    $feed_config = $feed->getConfigFor($this);
    $this->subscriber($feed->id())->unsubscribe($feed_config['source'], url($this->path($feed->id()), array('absolute' => TRUE)));
  }

  /**
   * {@inheritdoc}
   */
  public function importPeriod(FeedInterface $feed) {
    if ($this->subscriber($feed->id())->subscribed()) {
      // Delay for three days if there is a successful subscription.
      return 259200;
    }
  }

  /**
   * Convenience method for instantiating a subscriber object.
   */
  protected function subscriber($subscriber_id) {
    return PushSubscriber::instance($this->id, $subscriber_id, 'Drupal\feeds\PuSHSubscription', PuSHEnvironment::instance());
  }

}
