<?php

/**
 * @file
 * Contains Drupal\feeds\Plugin\ProcessorBase.
 *
 * @todo This needs to be sorted with EntityProcessor.
 */

namespace Drupal\feeds\Plugin;

use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\Exception\EntityAccessException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Result\ParserResultInterface;
use Drupal\feeds\SchedulerInterface;
use Drupal\feeds\StateInterface;

/**
 * Abstract class, defines helpers for processors.
 */
abstract class ProcessorBase extends ConfigurablePluginBase implements ClearableInterface {

  /**
   * {@inheritdoc}
   *
   * @todo This should be moved to the EntityProcessor.
   */
  public function clear(FeedInterface $feed) {
    $state = $feed->state(StateInterface::CLEAR);

    // Build base select statement.
    $info = $this->entityInfo();
    $select = db_select($info['base_table'], 'e');
    $select->addField('e', $info['entity_keys']['id'], 'entity_id');
    $select->join(
      'feeds_item',
      'fi',
      "e.{$info['entity_keys']['id']} = fi.entity_id AND fi.entity_type = '{$this->entityType()}'");
    $select->condition('fi.fid', $feed->id());

    // If there is no total, query it.
    if (!$state->total) {
      $state->total = $select->countQuery()
        ->execute()
        ->fetchField();
    }

    // Delete a batch of entities.
    $entities = $select->range(0, $this->getLimit())->execute();
    $entity_ids = array();
    foreach ($entities as $entity) {
      $entity_ids[$entity->entity_id] = $entity->entity_id;
    }
    $this->entityDeleteMultiple($entity_ids);

    // Report progress, take into account that we may not have deleted as
    // many items as we have counted at first.
    if (count($entity_ids)) {
      $state->deleted += count($entity_ids);
      $state->progress($state->total, $state->deleted);
    }
    else {
      $state->progress($state->total, $state->total);
    }

    // Report results when done.
    if ($feed->progressClearing() == StateInterface::BATCH_COMPLETE) {
      if ($state->deleted) {
        $message = format_plural(
          $state->deleted,
          'Deleted @number @entity',
          'Deleted @number @entities',
          array(
            '@number' => $state->deleted,
            '@entity' => strtolower($info['label']),
            '@entities' => strtolower($info['label_plural']),
          )
        );
        $feed->log('clear', $message, array(), WATCHDOG_INFO);
        drupal_set_message($message);
      }
      else {
        drupal_set_message(t('There are no @entities to be deleted.', array('@entities' => $info['label_plural'])));
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * @todo Get rid of the variable_get() here.
   */
  public function getLimit() {
    return variable_get('feeds_process_limit', ProcessorInterface::PROCESS_LIMIT);
  }

  /**
   * {@inheritdoc}
   */
  public function expire(FeedInterface $feed, $time = NULL) {
    $state = $feed->state(StateInterface::EXPIRE);
    if ($time === NULL) {
      $time = $this->expiryTime();
    }
    if ($time == SchedulerInterface::EXPIRE_NEVER) {
      return;
    }

    $select = $this->expiryQuery($feed, $time);
    // If there is no total, query it.
    if (!$state->total) {
      $state->total = $select->countQuery()->execute()->fetchField();
    }

    // Delete a batch of entities.
    $entity_ids = $select->range(0, $this->getLimit())->execute()->fetchCol();
    if ($entity_ids) {
      $this->entityDeleteMultiple($entity_ids);
      $state->deleted += count($entity_ids);
      $state->progress($state->total, $state->deleted);
    }
    else {
      $state->progress($state->total, $state->total);
    }
  }

  /**
   * Returns a database query used to select entities to expire.
   *
   * Processor classes should override this method to set the age portion of the
   * query.
   *
   * @param FeedInterface $feed
   *   The feed source.
   * @param int $time
   *   Delete entities older than this.
   *
   * @return SelectQuery
   *   A select query to execute.
   *
   * @todo Move to EntityProcessor.
   */
  protected function expiryQuery(FeedInterface $feed, $time) {
    // Build base select statement.
    $info = $this->entityInfo();
    $id_key = db_escape_field($info['entity_keys']['id']);

    $select = db_select($info['base_table'], 'e');
    $select->addField('e', $info['entity_keys']['id'], 'entity_id');
    $select->join(
      'feeds_item',
      'fi',
      "e.$id_key = fi.entity_id AND fi.entity_type = :entity_type", array(
        ':entity_type' => $this->entityType(),
    ));
    $select->condition('fi.fid', $feed->id());

    return $select;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemCount(FeedInterface $feed) {
    return db_query("SELECT count(*) FROM {feeds_item} WHERE fid = :fid", array(':fid' => $feed->id()))->fetchField();
  }

  /**
   * Execute mapping on an item.
   *
   * This method encapsulates the central mapping functionality. When an item is
   * processed, it is passed through map() where the properties of $source_item
   * are mapped onto $target_item following the processor's mapping
   * configuration.
   *
   * For each mapping ParserInterface::getSourceElement() is executed to
   * retrieve the source element, then ProcessorBase::setTargetElement() is
   * invoked to populate the target item properly. Alternatively a
   * hook_x_targets_alter() may have specified a callback for a mapping target
   * in which case the callback is asked to populate the target item instead of
   * ProcessorBase::setTargetElement().
   */
  protected function map(FeedInterface $feed, array $item, $target_item, $item_info) {
    // @todo Revisit static cache.

    $sources = $this->importer->getParser()->getMappingSources();
    $targets = $this->getMappingTargets();
    $parser = $this->importer->getParser();

    // Many mappers add to existing fields rather than replacing them. Hence we
    // need to clear target elements of each item before mapping in case we are
    // mapping on a prepopulated item such as an existing node.
    foreach ($this->importer->getMappings() as $mapping) {
      list($field) = explode(':', $mapping['target']);
      unset($target_item->$field);
    }

    /*
    This is where the actual mapping happens: For every mapping we envoke
    the parser's getSourceElement() method to retrieve the value of the source
    element and pass it to the processor's setTargetElement() to stick it
    on the right place of the target item.

    If the mapping specifies a callback method, use the callback instead of
    setTargetElement().
    */

    $value = array();

    foreach ($this->importer->getMappings() as $mapping) {

      list($key, $subkey) = explode(':', $mapping['target'] . ':value');

      if (!isset($value[$key][$subkey])) {
        $value[$key][$subkey] = array();
      }

      // Retrieve source element's value from parser.
      if (isset($sources[$mapping['source']]) &&
          is_array($sources[$mapping['source']]) &&
          isset($sources[$mapping['source']]['callback']) &&
          is_callable($sources[$mapping['source']]['callback'])) {

        $callback = $sources[$mapping['source']]['callback'];
        $new_value = $callback($feed, $item, $mapping['source']);
      }
      else {
        $new_value = $parser->getSourceElement($feed, $item, $mapping['source']);
      }

      if (!is_array($new_value)) {
        $new_value = array($new_value);
      }

      $value[$key][$subkey] = array_merge($value[$key][$subkey], $new_value);
    }

    // Rearrange values into Drupal's field structure.
    $new_value = array();
    foreach ($value as $key => $vs) {
      foreach ($vs as $subkey => $v) {
        $delta = 0;
        foreach ($v as $avalue) {
          $new_value[$key][$delta][$subkey] = $avalue;
          $delta++;
        }
      }
    }

    foreach ($this->importer->getMappings() as $mapping) {

      list($key, $subkey) = explode(':', $mapping['target'] . ':value');

      // Map the source element's value to the target.
      if (isset($targets[$mapping['target']]) &&
          is_array($targets[$mapping['target']]) &&
          isset($targets[$mapping['target']]['callback']) &&
          is_callable($targets[$mapping['target']]['callback'])) {
        $callback = $targets[$mapping['target']]['callback'];
        $callback($feed, $target_item, $key, $new_value[$key], $mapping, $item_info);
      }
      else {
        $this->setTargetElement($feed, $target_item, $key, $new_value[$key], $mapping, $item_info);
      }
    }

    return $target_item;
  }

  /**
   * {@inheritdoc}
   */
  public function expiryTime() {
    return SchedulerInterface::EXPIRE_NEVER;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultConfiguration() {
    $defaults = array(
      'update_existing' => ProcessorInterface::SKIP_EXISTING,
      'input_format' => 'plain_text',
      'skip_hash_check' => FALSE,
    );

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {

    $form['skip_hash_check'] = array(
      '#type' => 'checkbox',
      '#title' => t('Force update'),
      '#description' => t('Forces the update of items even if the feed did not change.'),
      '#default_value' => $this->configuration['skip_hash_check'],
    );

    global $user;
    $formats = filter_formats($user);
    foreach ($formats as $format) {
      $format_options[$format->format] = $format->name;
    }
    $form['input_format'] = array(
      '#type' => 'select',
      '#title' => t('Text format'),
      '#description' => t('Select the input format for the body field of the nodes to be created.'),
      '#options' => $format_options,
      '#default_value' => $this->configuration['input_format'],
      '#required' => TRUE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingTargets() {

    return array(
      'url' => array(
        'label' => t('URL'),
        'description' => t('The external URL of the item. E. g. the feed item URL in the case of a syndication feed. May be unique.'),
        'optional_unique' => TRUE,
      ),
      'guid' => array(
        'label' => t('GUID'),
        'description' => t('The globally unique identifier of the item. E. g. the feed item GUID in the case of a syndication feed. May be unique.'),
        'optional_unique' => TRUE,
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetElement(FeedInterface $feed, $target_item, $key, $value, $mapping, \stdClass $item_info) {
    switch ($key) {
      case 'url':
      case 'guid':
        $item_info->$key = $value;
        break;

      default:
        $target_item->$key = $value;
        break;
    }
  }

  /**
   * Retrieve the target entity's existing id if available. Otherwise return 0.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The Feed currently being imported.
   * @param \Drupal\feeds\Result\ParserResultInterface $result
   *   A parser result object.
   *
   * @return int
   *   The serial id of an entity if found, 0 otherwise.
   *
   * @todo Move to EntityProcessor.
   */
  protected function existingEntityId(FeedInterface $feed, ParserResultInterface $result) {
    $query = db_select('feeds_item')
      ->fields('feeds_item', array('entity_id'))
      ->condition('fid', $feed->id())
      ->condition('entity_type', $this->entityType());

    // Iterate through all unique targets and test whether they do already
    // exist in the database.
    foreach ($this->uniqueTargets($feed, $result) as $target => $value) {
      switch ($target) {
        case 'url':
          $entity_id = $query->condition('url', $value)->execute()->fetchField();
          break;

        case 'guid':
          $entity_id = $query->condition('guid', $value)->execute()->fetchField();
          break;
      }
      if (isset($entity_id)) {
        // Return with the content id found.
        return $entity_id;
      }
    }

    return 0;
  }


  /**
   * Iterates over a target array and retrieves all sources that are unique.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed being imported.
   * @param \Drupal\feeds\Result\ParserResultInterface $result
   *   The parser result object.
   *
   * @return array
   *   An array where the keys are target field names and the values are the
   *   elements from the source item mapped to these targets.
   */
  public function uniqueTargets(FeedInterface $feed, ParserResultInterface $result) {
    $parser = $this->importer->getParser();
    $targets = array();
    foreach ($this->importer->getMappings() as $mapping) {
      if (!empty($mapping['unique'])) {
        // Invoke the parser's getSourceElement to retrieve the value for this
        // mapping's source.
        $targets[$mapping['target']] = $parser->getSourceElement($feed, $result->currentItem(), $mapping['source']);
      }
    }

    return $targets;
  }

  /**
   * Creates an MD5 hash of an item.
   *
   * Includes mappings so that items will be updated if the mapping
   * configuration has changed.
   *
   * @param array $item
   *   The item to hash.
   *
   * @return string
   *   Always returns a hash, even with empty, null, or false:
   *   - Empty arrays return 40cd750bba9870f18aada2478b24840a
   *   - Empty/NULL/FALSE strings return d41d8cd98f00b204e9800998ecf8427e
   *
   * @todo I really doubt the above is still true. Plus, who cares.
   */
  protected function hash(array $item) {
    return hash('md5', serialize($item) . serialize($this->importer->getMappings()));
  }

  /**
   * Retrieves the MD5 hash of $entity_id from the database.
   *
   * @param int $entity_id
   *   The entity id to get the hash for.
   *
   * @return string
   *   Empty string if no item is found, hash otherwise.
   *
   * @todo Move to EntityProcessor.
   */
  protected function getHash($entity_id) {

    if ($hash = db_query("SELECT hash FROM {feeds_item} WHERE entity_type = :type AND entity_id = :id", array(':type' => $this->entityType(), ':id' => $entity_id))->fetchField()) {
      // Return with the hash.
      return $hash;
    }
    return '';
  }

  /**
   * Creates a log message when an exception occured during import.
   *
   * @param \Exception $e
   *   The exception that was thrown during processing.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object that was being processed.
   * @param arary $item
   *   The parser result for this entity.
   *
   * @return string
   *   The message to log.
   *
   * @todo This no longer works due to circular references.
   * @todo Move to EntityProcessor.
   */
  protected function createLogMessage(\Exception $e, EntityInterface $entity, array $item) {
    include_once DRUPAL_ROOT . '/core/includes/utility.inc';
    $message = $e->getMessage();
    $message .= '<h3>Original item</h3>';
    $message .= '<pre>' . drupal_var_export($item). '</pre>';
    $message .= '<h3>Entity</h3>';
    $message .= '<pre>' . drupal_var_export($entity->getValue()) . '</pre>';
    return $message;
  }

  /**
   * Formats UNIX timestamps to readable strings.
   *
   * @param int $timestamp
   *   A UNIX timestamp.
   *
   * @return string
   *   A string in the format, "After (time)" or "Never."
   */
  public function formatExpire($timestamp) {
    if ($timestamp == SchedulerInterface::EXPIRE_NEVER) {
      return t('Never');
    }
    return t('after !time', array('!time' => format_interval($timestamp)));
  }

}
