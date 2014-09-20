<?php

/**
 * @file
 * Contains \Drupal\feeds\FeedInterface.
 */

namespace Drupal\feeds;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\feeds\Plugin\Type\FeedsPluginInterface;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a feeds_feed entity.
 */
interface FeedInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Represents an active feed.
   *
   * @var int
   */
  const ACTIVE = 1;

  /**
   * Represents an inactive feed.
   *
   * @var int
   */
  const INACTIVE = 0;

  /**
   * Returns the source of the feed.
   *
   * @return string
   *   The source of a feed.
   */
  public function getSource();

  /**
   * Returns the Importer object that this feed is expected to be used with.
   *
   * @return \Drupal\feeds\ImporterInterface
   *   The importer object.
   *
   * @throws \RuntimeException
   *   Thrown if the importer does not exist.
   */
  public function getImporter();

  /**
   * Returns the feed creation timestamp.
   *
   * @return int
   *   Creation timestamp of the feed.
   */
  public function getCreatedTime();

  /**
   * Sets the feed creation timestamp.
   *
   * @param int $timestamp
   *   The feed creation timestamp.
   *
   * @return \Drupal\feeds\FeedInterface
   *   The called feed entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the feed imported timestamp.
   *
   * @return int
   *   Creation timestamp of the feed.
   */
  public function getImportedTime();

  /**
   * Runs the fetch and parse stages.
   *
   * @return \Drupal\feeds\Result\ParserResultInterface
   *   The result of the parsing stage.
   *
   * @throws
   *   Throws \Exception if an error occurs when fetching or parsing.
   */
  public function preview();

  /**
   * Starts importing a feed.
   *
   * This method starts an import job. Depending on the configuration of the
   * importer, a Batch API job or a background job with Job Scheduler will be
   * created.
   *
   * @throws \Exception
   *   If processing in background is enabled, the first batch chunk of the
   *   import will be executed on the current page request. This means that this
   *   method may throw the same exceptions as FeedInterface::import().
   */
  public function startImport();

  /**
   * Start deleting all imported items of a feed.
   *
   * This method starts a clear job. Depending on the configuration of the
   * importer, a Batch API job or a background job with Job Scheduler will be
   * created.
   *
   * @throws \Exception
   *   If processing in background is enabled, the first batch chunk of the
   *   clear task will be executed on the current page request. This means that
   *   this method may throw the same exceptions as FeedInterface::clear().
   */
  public function startClear();

  /**
   * Imports a feed.
   *
   * Executes the fetching, parsing and processing stage.
   *
   * This method only executes the current batch chunk, then returns. If you are
   * looking to import an entire source, use FeedInterface::startImport()
   * instead.
   *
   * @return float
   *   StateInterface::BATCH_COMPLETE if the import process finished. A decimal
   *   between 0.0 and 0.9 periodic if import is still in progress.
   *
   * @throws \Exception
   *   Throws Exception if an error occurs when importing.
   */
  public function import();

  /**
   * Imports a raw string.
   *
   * This does not batch. It assumes that the input is small enough to not need
   * it.
   *
   * @param string $raw
   *   (optional) A raw string to import. Defaults to null.
   *
   * @throws \Drupal\feeds\Exception\InterfaceNotImplementedException
   *   Thrown if the fetcher does not support real-time updates.
   * @throws \Exception
   *   Re-throws any exception that bubbles up.
   *
   * @todo We should document all possible exceptions, or at least the ones that
   *   can bubble up.
   *
   * @todo We need to create a job for this that will run immediately so that
   *   services don't have to wait for us to process. Can we spawn a background
   *   process?
   */
  public function importRaw($raw);

  /**
   * Removes all items from a feed.
   *
   * This method only executes the current batch chunk, then returns. If you are
   * looking to delete all items of a feed, use FeedInterface::startClear()
   * instead.
   *
   * @return float
   *   StateInterface::BATCH_COMPLETE if the clearing process finished. A
   *   decimal between 0.0 and 0.9 periodic if clearing is still in progress.
   *
   * @throws \Exception
   *   Throws Exception if an error occurs when clearing.
   */
  public function clear();

  /**
   * Removes all expired items from a feed.
   *
   * @return float
   *   StateInterface::BATCH_COMPLETE if the expiring process finished. A
   *   decimal between 0.0 and 0.9 periodic if it is still in progress.
   *
   * @throws \Exception
   *   Re-throws any exception that bubbles up.
   */
  public function expire();

  /**
   * Cleans up after an import.
   */
  public function cleanUp();

  /**
   * Reports the progress of the parsing stage.
   *
   * @return float
   *   A float between 0 and 1. 1 = StateInterface::BATCH_COMPLETE.
   */
  public function progressParsing();

  /**
   * Reports the progress of the import process.
   *
   * @return float
   *   A float between 0 and 1. 1 = StateInterface::BATCH_COMPLETE.
   */
  public function progressImporting();

  /**
   * Reports progress on clearing.
   */
  public function progressClearing();

  /**
   * Reports progress on expiry.
   */
  public function progressExpiring();

  /**
   * Returns a state object for a given stage.
   *
   * Lazily instantiates new states.
   *
   * @param string $stage
   *   One of StateInterface::FETCH, StateInterface::PARSE,
   *   StateInterface::PROCESS or StateInterface::CLEAR.
   *
   * @return \Drupal\feeds\StateInterface
   *   The State object for the given stage.
   */
  public function getState($stage);

  /**
   * @todo
   */
  public function setState($stage, $state);

  /**
   * @todo
   */
  public function clearState();

  /**
   * @todo
   */
  public function getFetcherResult();

  /**
   * @todo
   */
  public function setFetcherResult(FetcherResultInterface $result);

  /**
   * @todo
   */
  public function clearFetcherResult();

  /**
   * Counts items imported by this feed.
   *
   * @return int
   *   The number of items imported by this Feed.
   */
  public function getItemCount();

  /**
   * Only return source if configuration is persistent and valid.
   *
   * @return \Drupal\feeds\Entity\Feed
   *   The Feed object.
   *
   * @todo Figure out how to handle this.
   */
  public function existing();

  /**
   * Returns the configuration for a specific client plugin.
   *
   * @param \Drupal\feeds\Plugin\Type\FeedsPluginInterface $client
   *   A Feeds plugin.
   *
   * @return array
   *   The plugin configuration being managed by this Feed.
   */
  public function getConfigurationFor(FeedsPluginInterface $client);

  /**
   * Sets the configuration for a specific client plugin.
   *
   * @param \Drupal\feeds\Plugin\Type\FeedsPluginInterface $client
   *   A Feeds plugin.
   * @param array $config
   *   The configuration for the plugin.
   *
   * @return self
   *   Returns the Feed for method chaining.
   *
   * @todo Refactor this. This can cause conflicts if different plugin types
   *   use the same id.
   */
  public function setConfigurationFor(FeedsPluginInterface $client, array $config);

  /**
   * Writes to {feeds_log}.
   *
   * @param string $type
   *   The log type.
   * @param string $message
   *   The log message.
   * @param array $variables
   *   (optioanl) Variables used when translating the log message. Defaults to
   *   an empty array.
   * @param int $severity
   *   (optional) The severity of the log message. One of:
   *   - WATCHDOG_EMERGENCY
   *   - WATCHDOG_ALERT
   *   - WATCHDOG_CRITICAL
   *   - WATCHDOG_ERROR
   *   - WATCHDOG_WARNING
   *   - WATCHDOG_NOTICE
   *   - WATCHDOG_INFO
   *   - WATCHDOG_DEBUG
   *   Defaults to WATCHDOG_NOTICE.
   *
   * @return self
   *   Returns the Feed for method chaining.
   *
   * @todo Redo the static thingy.
   * @todo Figure out what Drupal 8 does with logging. Maybe we can use it.
   */
  public function log($type, $message, $variables = array(), $severity = WATCHDOG_NOTICE);

  /**
   * Returns the feed active status.
   *
   * Inactive feeds do not get imported.
   *
   * @return bool
   *   TRUE if the feed is active.
   */
  public function isActive();

  /**
   * Sets the active status of a feed..
   *
   * @param bool $active
   *   True to set this feed to active, false to set it to inactive.
   *
   * @return \Drupal\feeds\FeedInterface
   *   The called feed entity.
   */
  public function setActive($active);

  /**
   * Unlocks a feed.
   *
   * @return \Drupal\feeds\FeedInterface
   *   The called feed entity.
   */
  public function unlock();

}
