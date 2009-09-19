<?php
// $Id$

/**
 * Class definition for Common Syndication Parser.
 * 
 * Parses RSS and Atom feeds.
 */
class FeedsSyndicationParser extends FeedsParser {

  /**
   * Parses a raw string and returns a Feed object from it.
   */
  public function parse($raw) {
    
  }

  /**
   * Return mapping sources.
   */
  public function getMappingSources() {
    return array('title', 'description', 'url', 'guid', 'tags');
  }
}