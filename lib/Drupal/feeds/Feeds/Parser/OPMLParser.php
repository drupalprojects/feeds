<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\Parser\OPMLParser.
 *
 * @todo TESTS!!!!!!!!!!!!!
 * @todo Batch correctly.
 */

namespace Drupal\feeds\Feeds\Parser;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\feeds\Component\GenericOPMLParser;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResult;
use Drupal\feeds\Plugin\ParserInterface;
use Drupal\feeds\Plugin\PluginBase;

/**
 * Defines an OPML feed parser.
 *
 * @Plugin(
 *   id = "opml",
 *   title = @Translation("OPML"),
 *   description = @Translation("Parse OPML files.")
 * )
 */
class OPMLParser extends PluginBase implements ParserInterface {

  /**
   * {@inheritdoc}
   */
  public function parse(FeedInterface $feed, FetcherResultInterface $fetcher_result) {
    $parser = new GenericOPMLParser($fetcher_result->getRaw());
    $opml = $parser->parse(TRUE);
    $result = new ParserResult();

    $result->items = $this->getItems($opml['outlines'], array());

    $result->title = $opml['head']['#title'];

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
      $outline += array(
        '#title' => '',
        '#text' => '',
        '#xmlurl' => '',
        '#htmlurl' => '',
        'outlines' => array(),
      );

      $item = array();
      // Assume it is an actual feed if the URL is set.
      if ($outline['#xmlurl']) {
        if ($outline['#title']) {
          $item['title'] = $outline['#title'];
        }
        else {
          $item['title'] = $outline['#text'];
        }
        $item['categories'] = $categories;
        $item['xmlurl'] = $outline['#xmlurl'];
        $item['htmlurl'] = $outline['htmlurl'];

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
