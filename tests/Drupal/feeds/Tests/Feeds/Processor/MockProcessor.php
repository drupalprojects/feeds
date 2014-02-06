<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Feeds\Processor\MockProcessor.
 */

namespace Drupal\feeds\Tests\Feeds\Processor;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\Processor\ProcessorInterface;
use Drupal\feeds\Result\ParserResultInterface;
use Drupal\feeds\StateInterface;

/**
 * Defines a mock processor.
 *
 * @Plugin(
 *   id = "mock_processor",
 *   title = @Translation("Mock"),
 *   description = @Translation("A mock processor.")
 * )
 */
class MockProcessor implements ProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function process(FeedInterface $feed, StateInterface $state, ParserResultInterface $parser_result) {
  }

  /**
   * {@inheritdoc}
   */
  public function getLimit() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function expire(FeedInterface $feed, $time = NULL) {

  }

  /**
   * {@inheritdoc}
   */
  public function getItemCount(FeedInterface $feed) {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function expiryTime() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingTargets() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function pluginType() {
    return 'processor';
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'mock_processor';
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    return array();
  }

}
