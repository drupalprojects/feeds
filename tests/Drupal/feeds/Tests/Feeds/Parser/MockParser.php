<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Feeds\Parser\MockParser.
 */

namespace Drupal\feeds\Tests\Feeds\Parser;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\Parser\ParserInterface;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResult;

/**
 * Defines a mock parser.
 *
 * @Plugin(
 *   id = "mock_parser",
 *   title = @Translation("Mock"),
 *   description = @Translation("A mock parser.")
 * )
 */
class MockParser implements ParserInterface {

  /**
   * {@inheritdoc}
   */
  public function parse(FeedInterface $feed, FetcherResultInterface $fetcher_result) {
    return new ParserResult();
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingSources() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function pluginType() {
    return 'parser';
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'mock_parser';
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    return array();
  }

}
