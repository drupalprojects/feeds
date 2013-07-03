<?php

/**
 * @file
 * Contains \Drupal\feeds\Exception\LockException.
 */

namespace Drupal\feeds\Exception;

use RuntimeException;

/**
 * Thrown when Feeds can not obtain a lock.
 */
class LockException extends RuntimeException {}
