<?php

/**
 * @file
 * Contains \Drupal\feeds\Exception\EmptyFeedException.
 */

namespace Drupal\feeds\Exception;

use RuntimeException;

/**
 * Thrown if a feed is empty to abort importing.
 */
class EmptyFeedException extends RuntimeException {}
