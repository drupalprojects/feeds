<?php

/**
 * @file
 * Contains \Drupal\feeds\ItemInfoController.
 */

namespace Drupal\feeds;

use Drupal\Core\Database\Connection;

/**
 * Tracks metadata for feed items.
 */
class ItemInfoController implements ItemInfoControllerInterface {

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
  protected $escapedTable;

  /**
   * Constructs an ItemInfoController object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection object.
   * @param string $table
   *   The database table to perform queries on.
   */
  public function __construct(Connection $connection, $table) {
    $this->connection = $connection;
    $this->table = $table;
    $this->escapedTable = $connection->escapeTable($table);
  }

  /**
   * {@inheritdoc}
   */
  public function load($entity_type, $entity_id) {
    $result = $this->connection->query(
      'SELECT * FROM {' . $this->escapedTable . '}
      WHERE entity_type = :entity_type AND entity_id = :entity_id',
      array(':entity_type' => $entity_type, ':entity_id' => $entity_id)
    )->fetchArray();

    if ($result) {
      return (object) array(
        'entityType' => $result->entity_type,
        'entityId' => $result->entity_id,
        'fid' => $result->fid,
        'imported' => $result->imported,
        'url' => $result->url,
        'guid' => $result->guid,
        'hash' => $result->hash,
      );
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Grab the schema and do a diff.
   */
  public function save(\stdClass $item_info) {
    $this->connection->merge($this->table)
      ->key(array(
        'entity_type' => $item_info->entityType,
        'entity_id' => $item_info->entityId,
      ))
      ->fields(array(
        'fid' => $item_info->fid,
        'imported' => $item_info->imported,
        'url' => $item_info->url,
        'guid' => $item_info->guid,
        'hash' => $item_info->hash,
      ))
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function delete($entity_type, $entity_id) {
    return (bool) $this->connection->delete($this->table)
      ->condition('entity_type', $entity_type)
      ->condition('entity_id', $entity_id)
      ->execute();
  }

}
