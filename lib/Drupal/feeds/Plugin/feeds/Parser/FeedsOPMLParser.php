<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\Parser\FeedsOPMLParser.
 */

namespace Drupal\feeds\Plugin\Parser;

use Drupal\feeds\ParserOPML;

/**
 * Feeds parser plugin that parses OPML feeds.
 */
class FeedsOPMLParser extends ParserBase {

  /**
   * Implements ParserBase::parse().
   */
  public function parse(Feed $feed, FeedsFetcherResult $fetcher_result) {
    $opml = ParserOPML::parse($fetcher_result->getRaw());
    $result = new FeedsParserResult($opml['items']);
    $result->title = $opml['title'];
    return $result;
  }

  /**
   * Return mapping sources.
   */
  public function getMappingSources() {
    return array(
      'title' => array(
        'name' => t('Feed title'),
        'description' => t('Title of the feed.'),
      ),
      'xmlurl' => array(
        'name' => t('Feed URL'),
        'description' => t('URL of the feed.'),
      ),
    ) + parent::getMappingSources();
  }

}
