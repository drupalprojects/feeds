<?php

/**
 * @file
 * Contains FeedsParser and related classes.
 */

namespace Drupal\feeds\Plugin;

use Drupal\feeds\FeedsResult;
use Drupal\feeds\FeedsSource;
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
   * @param FeedsSource $source
   *   Source information.
   * @param $fetcher_result
   *   FeedsFetcherResult returned by fetcher.
   */
  public abstract function parse(FeedsSource $source, FeedsFetcherResult $fetcher_result);

  /**
   * Clear all caches for results for given source.
   *
   * @param FeedsSource $source
   *   Source information for this expiry. Implementers can choose to only clear
   *   caches pertaining to this source.
   */
  public function clear(FeedsSource $source) {}

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
    $content_type = feeds_importer($this->id)->config['content_type'];
    drupal_alter('feeds_parser_sources', $sources, $content_type);
    if (!feeds_importer($this->id)->config['content_type']) {
      return $sources;
    }
    $sources['parent:uid'] = array(
      'name' => t('Feed node: User ID'),
      'description' => t('The feed node author uid.'),
    );
    $sources['parent:nid'] = array(
      'name' => t('Feed node: Node ID'),
      'description' => t('The feed node nid.'),
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
  public function getSourceElement(FeedsSource $source, FeedsParserResult $result, $element_key) {

    switch ($element_key) {

      case 'parent:uid':
        if ($source->feed_nid && $node = node_load($source->feed_nid)) {
          return $node->uid;
        }
        break;
      case 'parent:nid':
        return $source->feed_nid;
    }

    $item = $result->currentItem();
    return isset($item[$element_key]) ? $item[$element_key] : '';
  }
}
