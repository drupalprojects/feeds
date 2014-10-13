<?php

namespace Drupal\feeds\Component;

class HttpHelpers {

  public static function findLinkHeader(array $headers, $rel) {
    $headers = array_change_key_case($headers);

    if (!isset($headers['link'])) {
      return;
    }

    foreach ((array) $headers['link'] as $link) {
      if ($link = static::parseLink($link, $rel)) {
        return $link;
      }
    }
  }

  /**
   * Finds a hub link from a Link header.
   *
   * @param string $link_header
   *   The full link header string.
   * @param string $rel
   *   The relationship to find.
   *
   * @return string
   *   The link, or an empty string if one wasn't found.
   */
  public static function parseLink($link_header, $rel) {
    if (preg_match('/^<(.*?)>;.*?rel=(\'|")' . $rel . '\2/i', trim($link_header), $matches)) {
      return trim($matches[1]);
    }

    return '';
  }

  public static function findHubFromXml($xml) {
    Reader::setExtensionManager(\Drupal::service('feed.bridge.reader'));
    $channel = Reader::importString($xml);

    $hubs = $channel->getHubs();
    return $hubs ? reset($hubs) : NULL;
  }

}
