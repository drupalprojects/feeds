<?php

/**
 * @file
 * Contains Drupal\feeds\Plugin\ParserInterface.
 */

namespace Drupal\feeds\Plugin;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\FetcherResultInterface;

/**
 * Abstract class, defines interface for parsers.
 */
interface ParserInterface extends FeedsPluginInterface {

  /**
   * Parse content returned by fetcher.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed we are parsing for.
   * @param \Drupal\feeds\FetcherResultInterface $fetcher_result
   *   Result returned by fetcher.
   */
  public function parse(FeedInterface $feed, FetcherResultInterface $fetcher_result);

  /**
   * Declare the possible mapping sources that this parser produces.
   *
   * @return array|false
   *   An array of mapping sources, or FALSE if the sources can be defined by
   *   typing a value in a text field.
   *
   *   Example:
   *   @code
   *   array(
   *     'title' => t('Title'),
   *     'created' => t('Published date'),
   *     'url' => t('FeedInterface item URL'),
   *     'guid' => t('FeedInterface item GUID'),
   *   )
   *   @endcode
   */
  public function getMappingSources();

  /**
   * Gets an element identified by $element_key of the given item.
   *
   * The element key corresponds to the values in the array returned by
   * ParserInterface::getMappingSources().
   *
   * This method is invoked from ProcessorInterface::map() when a concrete item
   * is processed.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed being parsed.
   * @param array $item
   *   The current item being processed.
   * @param string $element_key
   *   The key identifying the element that should be retrieved from $item.
   *
   * @return mixed
   *   The source element from $item identified by $element_key.
   */
  public function getSourceElement(FeedInterface $feed, array $item, $element_key);

}
