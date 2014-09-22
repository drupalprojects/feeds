<?php

/**
 * @file
 * Contains \Drupal\feeds\Event\ProcessEvent.
 */

namespace Drupal\feeds\Event;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\Result\ParserResultInterface;

/**
 * Fired to begin processing.
 */
class ProcessEvent extends EventBase {

  /**
   * The parser result.
   *
   * @var \Drupal\feeds\Result\ParserResultInterface
   */
  protected $parserResult;

  /**
   * Constructs a ProcessEvent object.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed.
   * @param \Drupal\feeds\Result\ParserResultInterface $parser_result
   *   The parser result.
   */
  public function __construct(FeedInterface $feed, ParserResultInterface $parser_result) {
    $this->feed = $feed;
    $this->parserResult = $parser_result;
  }

  /**
   * Returns the parser result.
   *
   * @return \Drupal\feeds\Result\ParserResultInterface
   *   The parser result.
   */
  public function getParserResult() {
    return $this->parserResult;
  }

}
