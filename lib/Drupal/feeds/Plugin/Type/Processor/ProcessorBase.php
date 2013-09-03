<?php

/**
 * @file
 * Contains Drupal\feeds\Plugin\Type\Processor\ProcessorBase.
 *
 * @todo This needs to be sorted with EntityProcessor.
 */

namespace Drupal\feeds\Plugin\Type\Processor;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\Exception\EntityAccessException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\Scheduler\SchedulerInterface;
use Drupal\feeds\Result\ParserResultInterface;
use Drupal\feeds\StateInterface;
use Drupal\feeds\Plugin\Type\ClearableInterface;
use Drupal\feeds\Plugin\Type\ConfigurablePluginBase;

/**
 * Abstract class, defines helpers for processors.
 */
abstract class ProcessorBase extends ConfigurablePluginBase implements ClearableInterface {

  /**
   * {@inheritdoc}
   *
   * @todo Get rid of the variable_get() here.
   */
  public function getLimit() {
    return variable_get('feeds_process_limit', ProcessorInterface::PROCESS_LIMIT);
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
   *
   * @todo Revisit static cache.
   */
  protected function map(FeedInterface $feed, array $item, $target_item) {
    $sources = $this->importer->getParser()->getMappingSources();
    $targets = $this->getMappingTargets();
    $parser = $this->importer->getParser();

    // Many mappers add to existing fields rather than replacing them. Hence we
    // need to clear target elements of each item before mapping in case we are
    // mapping on a prepopulated item such as an existing node.
    foreach ($this->importer->getMappings() as $mapping) {
      unset($target_item->{$mapping['target']});
    }

    $values = array();

    foreach ($this->importer->getMappings() as $mapping) {
      $target = $mapping['target'];

      foreach ($mapping['map'] as $column => $source) {

        if (!isset($values[$target][$column])) {
          $values[$target][$column] = array();
        }

        // Retrieve source element's value from parser.
        if (isset($sources[$source]) &&
            is_array($sources[$source]) &&
            isset($sources[$source]['callback']) &&
            is_callable($sources[$source]['callback'])) {

          $callback = $sources[$source]['callback'];
          $value = $callback($feed, $item, $source);
        }
        else {
          $value = $parser->getSourceElement($feed, $item, $source);
        }

        if (!is_array($value)) {
          $values[$target][$column][] = $value;
        }
        else {
          $values[$target][$column] = array_merge($values[$target][$column], $value);
        }
      }
    }

    // Rearrange values into Drupal's field structure.
    $new_values = array();
    foreach ($values as $target => $value) {
      foreach ($value as $column => $v) {
        $delta = 0;
        foreach ($v as $avalue) {
          $new_values[$target][$delta][$column] = $avalue;
          $delta++;
        }
      }
    }

    foreach ($this->importer->getMappings() as $delta => $mapping) {

      $target  = $mapping['target'];

      // Map the source element's value to the target.
      if ($plugin = $this->importer->getTargetPlugin($delta)) {
        $plugin->prepareValues($new_values[$target]);
      }

      $this->setTargetElement($feed, $target_item, $target, $new_values[$target], $mapping);
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
      '#title' => $this->t('Force update'),
      '#description' => $this->t('Forces the update of items even if the feed did not change.'),
      '#default_value' => $this->configuration['skip_hash_check'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetElement(FeedInterface $feed, $target_item, $key, $value, $mapping) {
    $target_item->$key = $value;
  }

  /**
   * Iterates over a target array and retrieves all sources that are unique.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed being imported.
   * @param array $item
   *   The parser result object.
   *
   * @return array
   *   An array where the keys are target field names and the values are the
   *   elements from the source item mapped to these targets.
   */
  public function uniqueTargets(FeedInterface $feed, array $item) {
    $parser = $this->importer->getParser();
    $targets = array();

    foreach ($this->importer->getMappings() as $mapping) {
      if (!empty($mapping['unique'])) {
        // Invoke the parser's getSourceElement to retrieve the value for this
        // mapping's source.
        $targets[$mapping['target']] = $parser->getSourceElement($feed, $item, $mapping['source']);
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
    $message .= '<pre>' . drupal_var_export($item) . '</pre>';
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
      return $this->t('Never');
    }
    return $this->t('after !time', array('!time' => format_interval($timestamp)));
  }

}
