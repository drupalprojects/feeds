<?php

/**
 * @file
 * Contains \Drupal\feeds\Xml\Utility.
 */

namespace Drupal\feeds\Xml;

/**
 * Simple XML helpers.
 */
class Utility {

  /**
   * Matches the characters of an XML element.
   *
   * @var string
   */
  protected static $elementRegex = '[:A-Z_a-z\\xC0-\\xD6\\xD8-\\xF6\\xF8-\\x{2FF}\\x{370}-\\x{37D}\\x{37F}-\\x{1FFF}\\x{200C}-\\x{200D}\\x{2070}-\\x{218F}\\x{2C00}-\\x{2FEF}\\x{3001}-\\x{D7FF}\\x{F900}-\\x{FDCF}\\x{FDF0}-\\x{FFFD}\\x{10000}-\\x{EFFFF}][:A-Z_a-z\\xC0-\\xD6\\xD8-\\xF6\\xF8-\\x{2FF}\\x{370}-\\x{37D}\\x{37F}-\\x{1FFF}\\x{200C}-\\x{200D}\\x{2070}-\\x{218F}\\x{2C00}-\\x{2FEF}\\x{3001}-\\x{D7FF}\\x{F900}-\\x{FDCF}\\x{FDF0}-\\x{FFFD}\\x{10000}-\\x{EFFFF}\\.\\-0-9\\xB7\\x{0300}-\\x{036F}\\x{203F}-\\x{2040}]*';

  /**
   * Strips the default namespaces from an XML string.
   *
   * @param string $xml
   *   The XML string.
   *
   * @return string
   *   The XML string with the default namespaces removed.
   */
  public static function removeDefaultNamespaces($xml) {
    return preg_replace('/(<' . static::$elementRegex . '[^>]*)\s+xmlns\s*=\s*("|\').*?(\2)([^>]*>)/u', '$1$4', $xml);
  }

}
