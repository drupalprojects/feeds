<?php

/**
 * @file
 * Contains \Drupal\feeds\Event\ProcessEvent.
 */

namespace Drupal\feeds\Event;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\Result\ParserResultInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 *
 */
class ProcessEvent extends Event {

  /**
   * The feed being imported.
   *
   * @var \Drupal\feeds\FeedInterface
   */
  protected $feed;

  /**
   * @param \Drupal\Core\Condition\ConditionPluginBag $conditions
   */
  public function __construct(FeedInterface $feed, ParserResultInterface $parser_result) {
    $this->feed = $feed;
    $this->parserResult = $parser_result;
  }

  public function getFeed() {
    return $this->feed;
  }

  public function getParserResult() {
    return $this->parserResult;
  }

}
