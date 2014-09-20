<?php

/**
 * @file
 * Contains \Drupal\feeds\Event\ParseEvent.
 */

namespace Drupal\feeds\Event;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResultInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 *
 */
class ParseEvent extends Event {

  /**
   * The feed being imported.
   *
   * @var \Drupal\feeds\FeedInterface
   */
  protected $feed;

  protected $fetcherResult;

  /**
   * The result of the fetcher.
   *
   * @var \Drupal\feeds\Result\ParserResultInterface
   */
  protected $parserResult;

  /**
   * @param \Drupal\Core\Condition\ConditionPluginBag $conditions
   */
  public function __construct(FeedInterface $feed, FetcherResultInterface $fetcher_result) {
    $this->feed = $feed;
    $this->fetcherResult = $fetcher_result;
  }

  public function getFeed() {
    return $this->feed;
  }

  public function getFetcherResult() {
    return $this->fetcherResult;
  }

  public function getParserResult() {
    return $this->parserResult;
  }

  public function setParserResult(ParserResultInterface $result) {
    $this->parserResult = $result;
  }

}
