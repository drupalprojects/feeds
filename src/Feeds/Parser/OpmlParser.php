<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\Parser\OpmlParser.
 */

namespace Drupal\feeds\Feeds\Parser;

use Drupal\feeds\Component\GenericOpmlParser;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\OpmlItem;
use Drupal\feeds\Plugin\Type\Parser\ParserInterface;
use Drupal\feeds\Plugin\Type\PluginBase;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResult;
use Drupal\feeds\StateInterface;

/**
 * Defines an OPML feed parser.
 *
 * @Plugin(
 *   id = "opml",
 *   title = @Translation("OPML"),
 *   description = @Translation("Parse OPML files.")
 * )
 */
class OpmlParser extends PluginBase implements ParserInterface {

  /**
   * {@inheritdoc}
   */
  public function parse(FeedInterface $feed, FetcherResultInterface $fetcher_result) {
    $raw = $fetcher_result->getRaw();
    if (!strlen(trim($raw))) {
      throw new EmptyFeedException();
    }

    $result = new ParserResult();

    $parser = new GenericOpmlParser($fetcher_result->getRaw());
    $opml = $parser->parse(TRUE);
    $items = $this->getItems($opml['outlines'], array());

    $state = $feed->getState(StateInterface::PARSE);
    if (!$state->total) {
      $state->total = count($items);
    }

    $start = (int) $state->pointer;
    $state->pointer = $start + $feed->getImporter()->getLimit();
    $state->progress($state->total, $state->pointer);

    foreach (array_slice($items, $start, $feed->getImporter()->getLimit()) as $item) {
      $result->addItem($item);
    }

    $result->set('title', $opml['head']['#title']);

    return $result;
  }

  /**
   * Returns a flattened array of feed items.
   *
   * @param array $outlines
   *   A nested array of outlines.
   * @param array $categories
   *   The parent categories.
   *
   * @return array
   *   The flattened list of feed items.
   */
  protected function getItems(array $outlines, array $categories) {
    $items = array();

    foreach ($outlines as $outline) {
      // PHPunit is being weird about our array appending.
      // @codeCoverageIgnoreStart
      $outline += [
        '#title' => '',
        '#text' => '',
        '#xmlurl' => '',
        '#htmlurl' => '',
        'outlines' => [],
      ];
      // @codeCoverageIgnoreEnd

      $item = new OpmlItem();
      // Assume it is an actual feed if the URL is set.
      if ($outline['#xmlurl']) {
        $outline['#title'] ?
        $item->set('title', $outline['#title']) :
        $item->set('title', $outline['#text']);

        $item->set('categories', $categories)
             ->set('xmlurl', $outline['#xmlurl'])
             ->set('htmlurl', $outline['#htmlurl']);

        $items[] = $item;
      }

      // Get sub elements.
      if ($outline['outlines']) {
        $sub_categories = array_merge($categories, array($outline['#text']));
        $items = array_merge($items, $this->getItems($outline['outlines'], $sub_categories));
      }
    }

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingSources() {
    return array(
      'title' => array(
        'label' => $this->t('Title'),
        'description' => $this->t('Title of the feed.'),
        'suggestions' => array(
          'targets' => array('subject', 'title', 'label', 'name'),
          'types' => array(
            'field_item:text' => array(),
          ),
        ),
      ),
      'xmlurl' => array(
        'label' => $this->t('URL'),
        'description' => $this->t('URL of the feed.'),
        'suggestions' => array(
          'targets' => array('url'),
        ),
      ),
      'categories' => array(
        'label' => $this->t('Categories'),
        'description' => $this->t('The categories of the feed.'),
        'suggestions' => array(
          'targets' => array('field_tags'),
          'types' => array(
            'field_item:taxonomy_term_reference' => array(),
          ),
        ),
      ),
      'htmlurl' => array(
        'label' => $this->t('Site URL'),
        'description' => $this->t('The URL of the site that provides the feed.'),
      ),
    );
  }

}
