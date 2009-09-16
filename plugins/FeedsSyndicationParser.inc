<?php
// $Id$

class FeedsSyndicationParser extends FeedsParser {

  public static function getName() {
    return t('Syndication Parser');
  }

  public static function getDescription() {
    return t('Parses RSS and Atom feeds.');
  }

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