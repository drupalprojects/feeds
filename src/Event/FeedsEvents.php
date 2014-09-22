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
   * Fired after fetching ends.
   */
  const POST_FETCH = 'feeds.post_fetch';

  /**
   * Fired when one or more feeds are deleted.
   */
  const FEEDS_DELETE = 'feeds.delete_multiple';

  const INIT_IMPORT = 'feeds.init_import';
  const FETCH = 'feeds.fetch';
  const PARSE = 'feeds.parse';
  const PROCESS = 'feeds.process';

  const INIT_CLEAR = 'feeds.init_clear';
  const CLEAR = 'feeds.clear';

  const INIT_EXPIRE = 'feeds.init_expire';
  const EXPIRE = 'feeds.expire';

}
