<?php

/**
 * @file
 * Contains Drupal\feeds\Plugin\ParserBase.
 */

namespace Drupal\feeds\Plugin;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedsResult;
use Drupal\feeds\FetcherResultInterface;
use Drupal\feeds\FeedsParserResult;

/**
 * Abstract class, defines interface for parsers.
 */
abstract class ParserBase extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function pluginType() {
    return 'parser';
  }

  /**
   * Parse content fetched by fetcher.
   *
   * Extending classes must implement this method.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   Source information.
   * @param \Drupal\feeds\FetcherResultInterface $fetcher_result
   *   Result returned by fetcher.
   */
  public abstract function parse(FeedInterface $feed, FetcherResultInterface $fetcher_result);

  /**
   * Clear all caches for results for given source.
   *
   * @param FeedInterface $feed
   *   Source information for this expiry. Implementers can choose to only clear
   *   caches pertaining to this source.
   */
  public function clear(FeedInterface $feed) {}

  /**
   * Declare the possible mapping sources that this parser produces.
   *
   * @return
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
  public function getMappingSources() {
    $sources = array();

    $definitions = \Drupal::service('plugin.manager.feeds.mapper')->getDefinitions();
    foreach ($definitions as $definition) {
      $mapper = \Drupal::service('plugin.manager.feeds.mapper')->createInstance($definition['id']);
      $mapper->sources($sources, $this->importer);
    }

    $sources['parent:uid'] = array(
      'name' => t('Feed: User ID'),
      'description' => t('The feed author uid.'),
    );
    $sources['parent:fid'] = array(
      'name' => t('Feed: ID'),
      'description' => t('The feed fid.'),
    );
    return $sources;
  }

  /**
   * Get an element identified by $element_key of the given item.
   * The element key corresponds to the values in the array returned by
   * ParserBase::getMappingSources().
   *
   * This method is invoked from ProcessorBase::map() when a concrete item is
   * processed.
   *
   * @param $batch
   *   FeedsImportBatch object containing the sources to be mapped from.
   * @param $element_key
   *   The key identifying the element that should be retrieved from $source
   *
   * @return
   *   The source element from $item identified by $element_key.
   *
   * @see ProcessorBase::map()
   * @see FeedsCSVParser::getSourceElement()
   */
  public function getSourceElement(FeedInterface $feed, FeedsParserResult $result, $element_key) {

    switch ($element_key) {
      case 'parent:uid':
        return $feed->uid->value;

      case 'parent:fid':
        return $feed->id();
    }

    $item = $result->currentItem();
    return isset($item[$element_key]) ? $item[$element_key] : '';
  }

}
