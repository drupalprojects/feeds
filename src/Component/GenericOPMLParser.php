<?php

/**
 * @file
 * Contains \Drupal\feeds\Component\GenericOPMLParser.
 */

namespace Drupal\feeds\Component;

/**
 * Parses a generic OPML string into an array.
 *
 * @todo Move this to Github.
 */
class GenericOPMLParser extends XMLParserBase {

  /**
   * The XPath query object.
   *
   * @var \DOMXpath
   */
  protected $xpath;

  /**
   * Whether to normalize the case of elements and attributes.
   *
   * @var bool
   */
  protected $normalizeCase;

  /**
   * Performs parsing of an OPML file.
   *
   * @param bool $normalize_case
   *   (optional) True to convert all attributes to lowercase. False, to leave
   *   them as is. Defaults to false.
   */
  protected function doParse($normalize_case = FALSE) {
    $this->normalizeCase = $normalize_case;

    $this->xpath = new \DOMXPath($this->doc);

    $return = array();
    // Title is a required field, let parsers assume its existence.
    $return['head'] = array('#title' => '');

    foreach ($this->xpath->query('/opml/head/*') as $element) {
      if ($this->normalizeCase) {
        $return['head']['#' . strtolower($element->nodeName)] = $element->nodeValue;
      }
      else {
        $return['head']['#' . $element->nodeName] = $element->nodeValue;
      }
    }

    if (isset($return['head']['#expansionState'])) {
      $return['head']['#expansionState'] = array_filter(explode(',', $head['#expansionState']));
    }

    $return['outlines'] = array();
    if ($content = $this->xpath->evaluate('/opml/body', $this->doc)->item(0)) {
      $return['outlines'] = $this->getOutlines();
    }

    return $return;
  }

  /**
   * Returns the sub-outline structure.
   *
   * @param \DOMElement $context
   *   The context element to iterate on.
   *
   * @return array
   *   The nested outline array.
   */
  protected function getOutlines(\DOMElement $context) {
    $outlines = array();

    foreach ($this->xpath->query('outline', $context) as $element) {
      $outline = array();
      if ($element->hasAttributes()) {
        foreach ($element->attributes as $attribute) {
          if ($this->normalizeCase) {
            $outline['#' . $attribute->nodeName] = $attribute->nodeValue;
          }
          else {
            $outline['#' . strtolower($attribute->nodeName)] = $attribute->nodeValue;
          }
        }
      }
      // Recurse.
      if ($sub_outlines = $this->getOutlines($element)) {
        $outline['outlines'] = $sub_outlines;
      }

      $outlines[] = $outline;
    }

    return $outlines;
  }

}
