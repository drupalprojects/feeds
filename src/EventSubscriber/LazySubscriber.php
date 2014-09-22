<?php

/**
 * @file
 * Contains \Drupal\feeds\EventSubscriber\LazySubscriber.
 */

namespace Drupal\feeds\EventSubscriber;

use Drupal\feeds\Event\ClearEvent;
use Drupal\feeds\Event\ExpireEvent;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\FetchEvent;
use Drupal\feeds\Event\InitEvent;
use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\Event\ProcessEvent;
use Drupal\feeds\Plugin\Type\ClearableInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event listener that registers Feeds pluings as event listeners.
 */
class LazySubscriber implements EventSubscriberInterface {

  /**
   * Wether the import listeners have been added.
   *
   * @var bool
   */
  protected $importInited = FALSE;

  /**
   * Wether the clear listeners have been added.
   *
   * @var bool
   */
  protected $clearInited = FALSE;

  /**
   * Wether the expire listeners have been added.
   *
   * @var bool
   */
  protected $expireInited = FALSE;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = array();
    $events[FeedsEvents::INIT_IMPORT][] = 'onInitImport';
    $events[FeedsEvents::INIT_CLEAR][] = 'onInitClear';
    $events[FeedsEvents::INIT_EXPIRE][] = 'onInitExpire';
    return $events;
  }

  /**
   * Adds import plugins as event listeners.
   */
  public function onInitImport(InitEvent $event, $event_name, EventDispatcherInterface $dispatcher) {
    if ($this->importInited === TRUE) {
      return;
    }
    $this->importInited = TRUE;

    $dispatcher->addListener(FeedsEvents::FETCH, function(FetchEvent $event) {
      $feed = $event->getFeed();
      $result = $feed->getImporter()->getFetcher()->fetch($feed);
      $event->setFetcherResult($result);
    });

    $dispatcher->addListener(FeedsEvents::PARSE, function(ParseEvent $event) {
      $feed = $event->getFeed();

      $result = $feed
        ->getImporter()
        ->getParser()
        ->parse($feed, $event->getFetcherResult());
      $event->setParserResult($result);
    });

    $dispatcher->addListener(FeedsEvents::PROCESS, function(ProcessEvent $event) {
      $feed = $event->getFeed();
      $feed
        ->getImporter()
        ->getProcessor()
        ->process($feed, $event->getParserResult());
    });
  }

  /**
   * Adds clear plugins as event listeners.
   */
  public function onInitClear(InitEvent $event, $event_name, EventDispatcherInterface $dispatcher) {
    if ($this->clearInited === TRUE) {
      return;
    }
    $this->clearInited = TRUE;

    foreach ($event->getFeed()->getImporter()->getPlugins() as $plugin) {
      if (!$plugin instanceof ClearableInterface) {
        continue;
      }

      $dispatcher->addListener(FeedsEvents::CLEAR, function(ClearEvent $event) use ($plugin) {
        $plugin->clear($event->getFeed());
      });
    }
  }

  /**
   * Adds expire plugins as event listeners.
   */
  public function onInitExpire(InitEvent $event, $event_name, EventDispatcherInterface $dispatcher) {
    if ($this->expireInited === TRUE) {
      return;
    }
    $this->expireInited = TRUE;

    $dispatcher->addListener(FeedsEvents::EXPIRE, function(ExpireEvent $event) {
      $feed = $event->getFeed();
      $feed->getImporter()->getProcessor()->expire($feed);
    });
  }

}
