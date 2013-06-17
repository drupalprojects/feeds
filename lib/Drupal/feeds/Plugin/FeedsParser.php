<?php

/**
 * @file
 * Contains FeedsParser and related classes.
 */

namespace Drupal\feeds\Plugin;

use Drupal\feeds\FeedsResult;
use Drupal\feeds\Plugin\Core\Entity\Feed;
use Drupal\feeds\FeedsFetcherResult;
use Drupal\feeds\FeedsParserResult;

/**
 * Abstract class, defines interface for parsers.
 */
abstract class FeedsParser extends FeedsPlugin {

  /**
   * Implements FeedsPlugin::pluginType().
   */
  public function pluginType() {
    return 'parser';
  }

  /**
   * Parse content fetched by fetcher.
   *
   * Extending classes must implement this method.
   *
   * @param Feed $source
   *   Source information.
   * @param $fetcher_result
   *   FeedsFetcherResult returned by fetcher.
   */
  public abstract function parse(Feed $source, FeedsFetcherResult $fetcher_result);

  /**
   * Clear all caches for results for given source.
   *
   * @param Feed $source
   *   Source information for this expiry. Implementers can choose to only clear
   *   caches pertaining to this source.
   */
  public function clear(Feed $source) {}

  /**
   * Declare the possible mapping sources that this parser produces.
   *
   * @ingroup mappingapi
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
   *     'url' => t('Feed item URL'),
   *     'guid' => t('Feed item GUID'),
   *   )
   *   @endcode
   */
  public function getMappingSources() {
    feeds_load_mappers();
    $sources = array();
    $importer_id = $this->importer->id();
    drupal_alter('feeds_parser_sources', $sources, $importer_id);
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
   * FeedsParser::getMappingSources().
   *
   * This method is invoked from FeedsProcessor::map() when a concrete item is
   * processed.
   *
   * @ingroup mappingapi
   *
   * @param $batch
   *   FeedsImportBatch object containing the sources to be mapped from.
   * @param $element_key
   *   The key identifying the element that should be retrieved from $source
   *
   * @return
   *   The source element from $item identified by $element_key.
   *
   * @see FeedsProcessor::map()
   * @see FeedsCSVParser::getSourceElement()
   */
  public function getSourceElement(Feed $feed, FeedsParserResult $result, $element_key) {

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
