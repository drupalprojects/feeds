<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Item\SyndicationItem.
 */

namespace Drupal\feeds\Feeds\Item;

/**
 * Defines an item class for use with an RSS/Atom parser.
 */
class SyndicationItem extends BaseItem {

  protected $title;
  protected $description;
  protected $author_name;
  protected $timestamp;
  protected $url;
  protected $guid;
  protected $tags;
  protected $geolocations;
  protected $enclosures;

}
