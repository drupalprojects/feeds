<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Zend\Extension\Georss\EntryTest.
 */

namespace Drupal\feeds\Tests\Zend\Extension\Georss;

use Drupal\feeds\Tests\FeedsUnitTestCase;
use Drupal\feeds\Zend\Extension\Georss\Entry;

/**
 * @covers \Drupal\feeds\Zend\Extension\Georss\Entry
 * @group Feeds
 */
class EntryTest extends FeedsUnitTestCase {

  public function test() {
    $text = '<feed xmlns:georss="http://www.georss.org/georss">';
    $text .= '<entry><georss:point>45.256 -71.92</georss:point></entry>';
    $text .= '</feed>';

    $doc = new \DOMDocument();
    $doc->loadXML($text);

    $entry = new Entry();
    $entry->setXpath(new \DOMXPath($doc));

    $entry->setEntryElement($doc->getElementsByTagName('entry')->item(0));

    $point = $entry->getGeoPoint();
    $this->assertSame(45.256, $point['lat']);
    $this->assertSame(-71.92, $point['lon']);
  }

}
