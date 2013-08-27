<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Parser\SyndicationParser.
 */

namespace Drupal\feeds\Plugin\feeds\Parser;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Result\ParserResult;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Plugin\ParserInterface;
use Drupal\feeds\Plugin\PluginBase;
use Zend\Feed\Reader\Reader;
use Zend\Feed\Reader\Exception\ExceptionInterface;

/**
 * Defines an RSS and Atom feed parser.
 *
 * @Plugin(
 *   id = "syndication",
 *   title = @Translation("Syndication parser"),
 *   description = @Translation("Default parser for RSS, Atom and RDF feeds.")
 * )
 */
class SyndicationParser extends PluginBase implements ParserInterface {

  /**
   * {@inheritdoc}
   */
  public function parse(FeedInterface $feed, FetcherResultInterface $fetcher_result) {
    $result = new ParserResult();
    Reader::setExtensionManager(\Drupal::service('feed.bridge.reader'));

    try {
      $channel = Reader::importString($fetcher_result->getRaw());
    }
    catch (ExceptionInterface $e) {
      watchdog_exception('feeds', $e);
      drupal_set_message(t('The feed from %site seems to be broken because of error "%error".', array('%site' => $feed->label(), '%error' => $e->getMessage())), 'error');
      return $result;
    }

    $result->title = $channel->getTitle();
    $result->description = $channel->getDescription();
    $result->link = $channel->getLink();

    foreach ($channel as $item) {
      // Reset the parsed item.
      $parsed_item = array();
      // Move the values to an array as expected by processors.
      $parsed_item['title'] = $item->getTitle();
      $parsed_item['guid'] = $item->getId();
      $parsed_item['url'] = $item->getLink();
      $parsed_item['description'] = $item->getDescription();

      if ($enclosure = $item->getEnclosure()) {
        $parsed_item['enclosures'][] = urldecode($enclosure->url);
      }

      if ($author = $item->getAuthor()) {
        $parsed_item['author_name'] = $author['name'];
      }
      if ($date = $item->getDateModified()) {
        $parsed_item['timestamp'] = $date->getTimestamp();
      }
      $parsed_item['tags'] = $item->getCategories()->getValues();

      $result->items[] = $parsed_item;
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingSources() {
    return array(
      'title' => array(
        'label' => t('Title'),
        'description' => t('Title of the feed item.'),
        'suggestions' => array(
          'targets' => array('title'),
          'types' => array(
            'field_item:text' => array(),
          )
        ),
      ),
      'description' => array(
        'label' => t('Description'),
        'description' => t('Description of the feed item.'),
        'suggested' => array('body'),
        'suggestions' => array(
          'targets' => array('body'),
          'types' => array(
            'field_item:text_with_summary' => array(),
          ),
        ),
      ),
      'author_name' => array(
        'label' => t('Author name'),
        'description' => t('Name of the feed item\'s author.'),
        'suggestions' => array(
          'types' => array(
            'entity_reference_field' => array('target_type' => 'user'),
          ),
        ),
      ),
      'timestamp' => array(
        'label' => t('Published date'),
        'description' => t('Published date as UNIX time GMT of the feed item.'),
        'suggestions' => array(
          'targets' => array('created'),
        ),
      ),
      'url' => array(
        'label' => t('Item URL (link)'),
        'description' => t('URL of the feed item.'),
        'suggestions' => array(
          'targets' => array('url'),
        ),
      ),
      'guid' => array(
        'label' => t('Item GUID'),
        'description' => t('Global Unique Identifier of the feed item.'),
        'suggestions' => array(
          'targets' => array('guid'),
        ),
      ),
      'tags' => array(
        'label' => t('Categories'),
        'description' => t('An array of categories that have been assigned to the feed item.'),
        'suggestions' => array(
          'targets' => array('field_tags'),
          'types' => array(
            'field_item:taxonomy_term_reference' => array(),
          ),
        ),
      ),
      'geolocations' => array(
        'label' => t('Geo Locations'),
        'description' => t('An array of geographic locations with a name and a position.'),
      ),
      'enclosures' => array(
        'label' => t('Enclosures'),
        'description' => t('A list of enclosures attached to the feed item.'),
        'suggestions' => array(
          'types' => array(
            'field_item:file' => array(),
          ),
        ),
      ),
    );
  }

}
