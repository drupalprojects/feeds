<?php

/**
 * @file
 * Contains \Drupal\feeds\FeedNotModifiedException.
 */

namespace Drupal\feeds;

use RuntimeException;

/**
 * Exception thrown if the feed has not been updated since the last run.
 */
class FeedNotModifiedException extends RuntimeException {}
