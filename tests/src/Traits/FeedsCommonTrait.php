<?php

namespace Drupal\Tests\feeds\Traits;

/**
 * Provides methods useful for Kernel and Functional Feeds tests.
 *
 * This trait is meant to be used only by test classes.
 */
trait FeedsCommonTrait {

  /**
   * Asserts that the given number of nodes exist.
   *
   * @param int $expected_node_count
   *   The expected number of nodes in the node table.
   * @param string $message
   *   (optional) The message to assert.
   */
  protected function assertNodeCount($expected_node_count, $message = '') {
    if (!$message) {
      $message = '@expected nodes have been created (actual: @count).';
    }

    $node_count = $this->container->get('database')
      ->select('node')
      ->fields('node', [])
      ->countQuery()
      ->execute()
      ->fetchField();
    static::assertEquals($expected_node_count, $node_count, strtr($message, [
      '@expected' => $expected_node_count,
      '@count' => $node_count,
    ]));
  }

  /**
   * Absolute path to Drupal root.
   */
  protected function absolute() {
    return realpath(getcwd());
  }

  /**
   * Get the absolute directory path of the feeds module.
   */
  protected function absolutePath() {
    return $this->absolute() . '/' . drupal_get_path('module', 'feeds');
  }

  /**
   * Get the absolute directory path of the resources folder.
   */
  protected function resourcesPath() {
    return $this->absolutePath() . '/tests/resources';
  }

}
