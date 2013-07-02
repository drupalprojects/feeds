<?php

/**
 * @file
 * Contains \Drupal\feeds\FetcherResultInterface.
 */

namespace Drupal\feeds;

/**
 * Defines the interface for result objects returned by fetcher pluings.
 */
interface FetcherResultInterface {

  /**
   * Gets the resource provided by the fetcher as a string.
   *
   * @return string
   *   The raw content from the source as a string.
   *
   * @throws \Exception
   *   Thrown if an unexpected problem occurred.
   */
  public function getRaw();

  /**
   * Gets the path to the file containing the resource provided by the fetcher.
   *
   * @return string
   *   A path to a file containing the raw content as a source.
   *
   * @throws \Exception
   *   Thrown if an unexpected problem occurred.
   */
  public function getFilePath();

}
