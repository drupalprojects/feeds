<?php

/**
 * @file
 * Contains \Drupal\feeds\PuSH\Subscription.
 */

namespace Drupal\feeds\PuSH;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Database\Connection;

/**
 * An SQL controller to manage PuSH subscriptions.
 *
 * @todo We need a cron job to re-lease expired leases.
 */
class Subscription implements SubscriptionInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The database table.
   *
   * @var string
   */
  protected $table;

  /**
   * The escaped database table.
   *
   * @var string
   */
  protected $tableEscaped;

  /**
   * Constructs a Subscription object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection object.
   * @param string $table
   *   The database table to perform queries on.
   */
  public function __construct(Connection $connection, $table) {
    $this->connection = $connection;
    $this->table = $table;
    $this->tableEscaped = $connection->escapeTable($this->table);
  }

  /**
   * {@inheritdoc}
   */
  public function setSubscription(array &$data) {

    if (isset($data['lease']) && is_numeric($data['lease'])) {
      $data['expires'] = (int) REQUEST_TIME + $data['lease'];
    }
    else {
      // @todo Change schema to allow NULL values.
      $data['lease'] = 0;
      $data['expires'] = 0;
    }

    // Updating an existing subscription.
    if ($this->hasSubscription($data['id'])) {
      unset($data['created']);

      $this->connection->update($this->table)
        ->fields($data)
        ->condition('id', $data['id'])
        ->execute();

      return FALSE;
    }

    // Creating a new subscription.
    else {
      $data['secret'] = substr(Crypt::randomBytesBase64(55), 0, 43);
      $data['token'] = substr(Crypt::randomBytesBase64(55), 0, 43);
      $data['created'] = REQUEST_TIME;

      $this->connection->insert($this->table)
        ->fields($data)
        ->execute();

      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscription($key) {
    return $this->connection->query(
      'SELECT * FROM {' . $this->tableEscaped . '} WHERE id = :key',
      array(':key' => $key)
    )->fetchAssoc();
  }

  /**
   * {@inheritdoc}
   */
  public function hasSubscription($key) {
    return (bool) $this->connection->query(
      'SELECT 1 FROM {' . $this->tableEscaped . '} WHERE id = :key',
      array(':key' => $key)
    )->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function hasSubscriptions(array $keys) {
    return $this->connection
      ->select($this->table)
      ->fields(array('id'))
      ->condition('id', $keys)
      ->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteSubscription($key) {
    return (bool) $this->connection
      ->delete($this->table)
      ->condition('id', $key)
      ->execute();
  }

}
