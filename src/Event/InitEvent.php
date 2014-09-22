<?php

/**
 * @file
 * Contains \Drupal\feeds\Event\InitEvent.
 */

namespace Drupal\feeds\Event;

use Drupal\feeds\FeedInterface;

/**
 * This event is fired before a regular event to allow listeners to lazily set
 * themselves up.
 */
class InitEvent extends EventBase {

}
