<?php

/**
 * @file
 * Contains \Drupal\feeds\Utility\Feed.
 */

namespace Drupal\feeds\Utility;

use Zend\Feed\Reader\FeedSet;
use Zend\Feed\Reader\Reader;

/**
 * Helper functions for dealing with feeds.
 */
class Feed {

  /**
   * Discovers RSS or Atom feeds from a document.
   *
   * If the document is an HTML document, this attempts to discover RSS or Atom
   * feeds referenced from the page.
   *
   * @param string $url
   *   The URL of the document.
   * @param string $document
   *   The document to find feeds in. Either an HTML or XML document.
   *
   * @return string|false
   *   The discovered feed, or false if a feed could not be found.
   */
  public static function getCommonSyndication($url, $document) {
    // If this happens to be a feed then just return the url.
    if (static::isFeed($document)) {
      return $url;
    }

    return static::findFeed($url, $document);
  }

  /**
   * Returns if the provided $content_type is a feed.
   *
   * @param string $document
   *   The actual HTML or XML document from the HTTP request.
   *
   * @return bool
   *   Returns true if this is a parsable feed, false if not.
   */
  public static function isFeed($data) {
    Reader::setExtensionManager(\Drupal::service('feed.bridge.reader'));

    try {
      $feed_type = Reader::detectType($data);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    return $feed_type != Reader::TYPE_ANY;
  }

  /**
   * Finds potential feed tags in an HTML document.
   *
   * @param string $url
   *   The URL of the document, to use as a base URL.
   * @param string $html
   *   The HTML document to search.
   *
   * @return string|false
   *   The URL of the first feed link found, or false if unable to find a link.
   */
  public static function findFeed($url, $html) {
    $use_error = libxml_use_internal_errors(TRUE);
    $entity_loader = libxml_disable_entity_loader(TRUE);

    $dom = new \DOMDocument();
    $status = $dom->loadHTML(trim($html));

    libxml_disable_entity_loader($entity_loader);
    libxml_use_internal_errors($use_error);

    if (!$status) {
      return FALSE;
    }

    $feed_set = new FeedSet();
    $feed_set->addLinks($dom->getElementsByTagName('link'), $url);

    // Load the first feed type found.
    foreach (['atom', 'rss', 'rdf'] as $feed_type) {
      if (isset($feed_set->$feed_type)) {
        return $feed_set->$feed_type;
      }
    }

    return FALSE;
  }

  /**
   * Copy of valid_url() that supports the webcal and feed schemes.
   *
   * @see valid_url()
   *
   * @todo Replace with valid_url() when http://drupal.org/node/295021 is fixed.
   */
  public static function validUrl($url, $absolute = FALSE) {
    if ($absolute) {
      return (bool) preg_match("
        /^                                                      # Start at the beginning of the text
        (?:ftp|https?|feed|webcal):\/\/                         # Look for ftp, http, https, feed or webcal schemes
        (?:                                                     # Userinfo (optional) which is typically
          (?:(?:[\w\.\-\+!$&'\(\)*\+,;=]|%[0-9a-f]{2})+:)*      # a username or a username and password
          (?:[\w\.\-\+%!$&'\(\)*\+,;=]|%[0-9a-f]{2})+@          # combination
        )?
        (?:
          (?:[a-z0-9\-\.]|%[0-9a-f]{2})+                        # A domain name or a IPv4 address
          |(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\])         # or a well formed IPv6 address
        )
        (?::[0-9]+)?                                            # Server port number (optional)
        (?:[\/|\?]
          (?:[|\w#!:\.\?\+=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})   # The path and query (optional)
        *)?
      $/xi", $url);
    }
    else {
      return (bool) preg_match("/^(?:[\w#!:\.\?\+=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})+$/i", $url);
    }
  }

}
