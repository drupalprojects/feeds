<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Feeds\Parser\SitemapParserTest.
 */

namespace Drupal\feeds\Tests\Feeds\Parser;

use Drupal\feeds\Feeds\Parser\SitemapParser;
use Drupal\feeds\Result\RawFetcherResult;
use Drupal\feeds\State;
use Drupal\feeds\StateInterface;
use Drupal\feeds\Tests\FeedsUnitTestCase;

/**
 * @covers \Drupal\feeds\Feeds\Parser\SitemapParser
 * @group Feeds
 */
class SitemapParserTest extends FeedsUnitTestCase {

  protected $parser;
  protected $importer;
  protected $feed;
  protected $state;

  public function setUp() {
    parent::setUp();

    $this->importer = $this->getMock('Drupal\feeds\ImporterInterface');
    $configuration = ['importer' => $this->importer];
    $this->parser = new SitemapParser($configuration, 'sitemap', []);
    $this->parser->setStringTranslation($this->getStringTranslationStub());

    $this->state = new State();

    $this->feed = $this->getMock('Drupal\feeds\FeedInterface');
    $this->feed->expects($this->any())
      ->method('getState')
      ->with(StateInterface::PARSE)
      ->will($this->returnValue($this->state));
    $this->feed->expects($this->any())
      ->method('getImporter')
      ->will($this->returnValue($this->importer));
  }

  public function testFetch() {
    $this->importer->expects($this->any())
      ->method('getLimit')
      ->will($this->returnValue(3));

    $file = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/tests/resources/sitemap-example.xml';
    $fetcher_result = new RawFetcherResult(file_get_contents($file));

    $result = $this->parser->parse($this->feed, $fetcher_result);
    $this->assertSame(count($result), 3);
    $this->assertSame($result[0]->get('url'), 'http://www.example.com/');

    // Parse again. Tests batching.
    $result = $this->parser->parse($this->feed, $fetcher_result);
    $this->assertSame(count($result), 2);
    $this->assertSame($result[0]->get('priority'), '0.3');
  }

  /**
   * @expectedException \Exception
   */
  public function testInvalidFeed() {
    $fetcher_result = new RawFetcherResult('beep boop');
    $result = $this->parser->parse($this->feed, $fetcher_result);
  }

  /**
   * @expectedException \Drupal\feeds\Exception\EmptyFeedException
   */
  public function testEmptyFeed() {
    $result = new RawFetcherResult('');
    $this->parser->parse($this->feed, $result);
  }

  public function testGetMappingSources() {
    // Not really much to test here.
    $this->assertSame(count($this->parser->getMappingSources()), 4);
  }

}

