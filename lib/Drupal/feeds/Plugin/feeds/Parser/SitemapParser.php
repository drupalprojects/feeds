<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Parser\SitemapParser.
 */

namespace Drupal\feeds\Plugin\feeds\Parser;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Result\ParserResult;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Plugin\ParserInterface;
use Drupal\feeds\Plugin\PluginBase;

/**
 * Defines a SitemapXML feed parser.
 *
 * @Plugin(
 *   id = "sitemap",
 *   title = @Translation("Sitemap XML"),
 *   description = @Translation("Parse Sitemap XML format feeds.")
 * )
 */
class SitemapParser extends PluginBase implements ParserInterface {

  /**
   * {@inheritdoc}
   */
  public function parse(FeedInterface $feed, FetcherResultInterface $fetcher_result) {
    // Set time zone to GMT for parsing dates with strtotime().
    $tz = date_default_timezone_get();
    date_default_timezone_set('GMT');
    // Yes, using a DOM parser is a bit inefficient, but will do for now.
    $xml = new \SimpleXMLElement($fetcher_result->getRaw());
    $result = new ParserResult();
    foreach ($xml->url as $url) {
      $item = array('url' => (string) $url->loc);
      if ($url->lastmod) {
        $item['lastmod'] = strtotime($url->lastmod);
      }
      if ($url->changefreq) {
        $item['changefreq'] = (string) $url->changefreq;
      }
      if ($url->priority) {
        $item['priority'] = (string) $url->priority;
      }
      $result->items[] = $item;
    }
    date_default_timezone_set($tz);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingSources() {
    return array(
      'url' => array(
        'label' => $this->t('Item URL (link)'),
        'description' => $this->t('URL of the feed item.'),
        'suggestions' => array(
          'targets' => array('url'),
        ),
      ),
      'lastmod' => array(
        'label' => $this->t('Last modification date'),
        'description' => $this->t('Last modified date as UNIX time GMT of the feed item.'),
      ),
      'changefreq' => array(
        'label' => $this->t('Change frequency'),
        'description' => $this->t('How frequently the page is likely to change.'),
      ),
      'priority' => array(
        'label' => $this->t('Priority'),
        'description' => $this->t('The priority of this URL relative to other URLs on the site.'),
      ),
    );
  }

}
