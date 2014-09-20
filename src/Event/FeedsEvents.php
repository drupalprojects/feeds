<?php

/**
 * @file
 * Contains \Drupal\feeds\Event\FeedsEvents.
 */

namespace Drupal\feeds\Event;

/**
 * Defines events for the Feeds module.
 */
final class FeedsEvents {

  /**
   * Fired before fetching begins.
   */
  const PRE_FETCH = 'feeds.pre_fetch';

  /**
   * Fired after fetching ends.
   */
  const POST_FETCH = 'feeds.post_fetch';

  /**
   * Fired before parsing begins.
   */
  const PRE_PARSE = 'feeds.pre_parse';

  /**
   * Fired after parsing ends.
   */
  const POST_PARSE = 'feeds.post_parse';

  /**
   * Fired when one or more feeds are deleted.
   */
  const FEEDS_DELETE = 'feeds.delete_multiple';

  const FETCH = 'feeds.fetch';
  const PARSE = 'feeds.parse';
  const PROCESS = 'feeds.process';
  const CLEAR = 'feeds.clear';
  const EXPIRE = 'feeds.expire';

}
