<?php

/**
 * @file
 * Contains \Drupal\feeds\Lock\PersistentDatabaseLockBackend.
 */

namespace Drupal\feeds\Lock;

use Drupal\Core\Database\Connection;
use Drupal\Core\Lock\DatabaseLockBackend;

/**
 * Defines the persistent database lock backend. This backend is global for this
 * Drupal installation.
 */
class PersistentDatabaseLockBackend extends DatabaseLockBackend {

  /**
   * Constructs a new PersistentDatabaseLockBackend.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    // This simply overrides the parent constructor, to not have it register
    // releaseAll() as a shutdown function.
    $this->database = $database;
    // Set the lockId to a fixed string to make the lock ID the same across
    // multiple requests.
    $this->lockId = 'persistent';
  }

}
