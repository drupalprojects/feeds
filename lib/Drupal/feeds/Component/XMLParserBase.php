<?php

/**
 * @file
 * Contains \Drupal\feeds\Component\XMLParserBase.
 */

namespace Drupal\feeds\Component;

/**
 * Defines a base class for parsing XML files.
 */
abstract class XMLParserBase {

  /**
   * The DOM document.
   *
   * @var \DOMDocument
   */
  protected $doc;

  /**
   * The previous value of libxml error reporting.
   *
   * @var bool
   */
  protected $useError;

  /**
   * The errors reported during parsing.
   *
   * @var array
   */
  protected $errors = array();

  /**
   * Constructs a new XMLParserBase object.
   *
   * @param string $xml
   *   The XML to parse.
   */
  public function __construct($xml) {
    $this->errorStart();
    $this->doc = $this->getDOMDocument($xml);
  }

  /**
   * Returns the DOMDocument to parse.
   *
   * Implementers can override this to setup the document.
   *
   * @param string $xml
   *   The XML to parse.
   *
   * @return \DOMDocuemnt
   *   The DOMDocument
   */
  protected function getDOMDocument($xml) {
    $doc = new \DOMDocument('1.0', 'utf-8');
    $doc->loadXML($xml);
    return $doc;
  }

  /**
   * Performs the parsing.
   *
   * @return mixed
   *   The return value is dependent on the parser.
   */
  public function parse() {
    $result = $this->doParse();
    $this->errorStop();
    return $result;
  }

  /**
   * Performs the actualy parsing.
   *
   * @return mized
   *   The result of parsing.
   */
  abstract protected function doParse();

  /**
   * Starts custom error handling.
   */
  protected function errorStart() {
    $this->useError = libxml_use_internal_errors(TRUE);
  }

  /**
   * Stops custom error handling.
   */
  protected function errorStop() {
    foreach (libxml_get_errors() as $error) {
      $this->errors[$error->level][] = array(
        'message' => trim($error->message),
        'line' => $error->line,
        'code' => $error->code,
      );
    }
    libxml_clear_errors();
    libxml_use_internal_errors($this->useError);
  }

  /**
   * Returns the errors reported during parsing.
   *
   * @return array
   *   An array of errors keyed by error level.
   */
  public function getErrors() {
    return $this->errors;
  }

}
