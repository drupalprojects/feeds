<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Feeds\Fetcher\MockFetcher.
 */

namespace Drupal\feeds\Tests\Feeds\Fetcher;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\FeedPluginFormInterface;
use Drupal\feeds\Plugin\Type\Fetcher\FetcherInterface;

/**
 * Defines a mock fetcher.
 *
 * @Plugin(
 *   id = "mock_fetcher",
 *   title = @Translation("Mock"),
 *   description = @Translation("A mock fetcher.")
 * )
 */
class MockFetcher implements FetcherInterface, FeedPluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function fetch(FeedInterface $feed) {
    return new FetcherResult(NULL);
  }

  public function pluginType() {
    return 'fetcher';
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'mock_fetcher';
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function buildFeedForm(array $form, array &$form_state, FeedInterface $feed) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function validateFeedForm(array &$form, array &$form_state, FeedInterface $feed) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitFeedForm(array &$form, array &$form_state, FeedInterface $feed) {

  }

}
