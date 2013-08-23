<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Target\Taxonomy.
 */

namespace Drupal\feeds\Plugin\feeds\Target;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedsElement;
use Drupal\feeds\Plugin\FieldTargetBase;
use Drupal\field\Entity\FieldInstance;
use Drupal\feeds\Entity\Importer;
use Drupal\taxonomy\Type\TaxonomyTermReferenceItem;

/**
 * Search by term name.
 */
const FEEDS_TAXONOMY_SEARCH_TERM_NAME = 0;

/**
 * Search by term id.
 */
const FEEDS_TAXONOMY_SEARCH_TERM_ID = 1;

/**
 * Search by GUID.
 */
const FEEDS_TAXONOMY_SEARCH_TERM_GUID = 2;

/**
 * Defines a taxonomy field mapper.
 *
 * @Plugin(
 *   id = "taxonomy",
 *   title = @Translation("Taxonomy")
 * )
 */
class Taxonomy extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  protected $fieldTypes = array('taxonomy_term_reference');

  protected $cache = array();

  /**
   * {@inheritdoc}
   */
  public function sources(array &$sources, Importer $importer) {

    foreach (field_info_instances('feeds_feed', $importer->id()) as $name => $instance) {
      $info = field_info_field($name);

      if ($info['type'] == 'taxonomy_term_reference') {
        $sources['parent:taxonomy:' . $info->label()] = array(
          'name' => t('Feed node: Taxonomy: @vocabulary', array('@vocabulary' => $instance->label())),
          'description' => t('Taxonomy terms from feed node.'),
          'callback' => array($this, 'getSource'),
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSource(FeedInterface $feed, array $item, $key) {
    list(, , $field) = explode(':', $key, 3);

    $result = array();
    foreach ($feed->$field as $term) {
      $result[] = $term;
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function applyTargets(FieldInstance $instance) {
    return array(
      $instance->getFieldName() => array(
        'name' => check_plain($instance->label()),
        'callback' => array($this, 'setTarget'),
        'description' => t('The @label field of the entity.', array('@label' => $instance->label())),
        'summary_callback' => array($this, 'summary'),
        'form_callback' => array($this, 'form'),
        'columns' => array('target_id'),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function validate($key, $value, array $mapping) {
    // Add in default values.
    $mapping += array(
      'term_search' => FEEDS_TAXONOMY_SEARCH_TERM_NAME,
      'autocreate' => FALSE,
    );

    $field_name = $this->instance->getFieldName();

    if (!isset($this->cache['allowed_values'][$field_name])) {
      $this->cache['allowed_values'][$field_name] = taxonomy_allowed_values($this->instance, $this->entity);
    }

    if (!isset($this->cache['allowed_vocabularies'][$field_name])) {
      foreach ($this->instance->getFieldSetting('allowed_values') as $tree) {
        $this->cache['allowed_vocabularies'][$field_name][$tree['vocabulary']] = $tree['vocabulary'];
      }
    }

    $tid = FALSE;

    if ($value instanceof TaxonomyTermReferenceItem) {
      $tid = $value->value;
    }
    else {
      switch ($mapping['term_search']) {

        // Lookup by name.
        case FEEDS_TAXONOMY_SEARCH_TERM_NAME:
          $tid = $this->termSearchByName($value, $mapping['autocreate']);
          break;

        // Lookup by tid.
        case FEEDS_TAXONOMY_SEARCH_TERM_ID:
          if (is_numeric($value)) {
            $tid = $value;
          }
          break;

        // Lookup by GUID.
        case FEEDS_TAXONOMY_SEARCH_TERM_GUID:
          $tid = $this->getTermByGUID($value);
          break;
      }
    }

    if ($tid && isset($this->cache['allowed_values'][$field_name][$tid])) {
      return $tid;
    }

    return FALSE;
  }

  /**
   * Looks up a term by GUID, assumes SQL storage backend.
   *
   * @param string $guid
   *   The Feeds GUID to compare against.
   *
   * @return int|FALSE
   *   The term id, or FALSE if one was not found.
   */
  protected function getTermByGUID($guid) {
    return db_select('feeds_item')
      ->fields('feeds_item', array('entity_id'))
      ->condition('entity_type', 'taxonomy_term')
      ->condition('guid', $guid)
      ->execute()
      ->fetchField();
  }

  /**
   *
   */
  protected function termSearchByName($value, $autocreate = FALSE) {

    $query = \Drupal::entityQuery('taxonomy_term');
    $tids = $query
      ->condition('vid', $this->cache['allowed_vocabularies'][$this->instance->getFieldName()])
      ->condition('name', $value)
      ->range(0, 1)
      ->execute();

    if ($tids) {
      return key($tids);
    }
    elseif ($autocreate) {
      $term = entity_create('taxonomy_term', array(
        'name' => $value,
        'vid' => key($this->cache['allowed_vocabularies'][$this->instance->getFieldName()]),
      ));
      $term->save();
      $tid = $term->id();
      // Add to the list of allowed values.
      $this->cache['allowed_values'][$this->instance->getFieldName()][$tid] = $term->label();
      return $tid;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function summary($mapping, $target, $form, $form_state) {
    $options = $this->callbackOptions();
    if (empty($mapping['term_search'])) {
      return t('Search taxonomy terms by: <strong>@search</strong>', array('@search' => $options[FEEDS_TAXONOMY_SEARCH_TERM_NAME]));
    }
    return t('Search taxonomy terms by: <strong>@search</strong>', array('@search' => $options[$mapping['term_search']]));
  }

  /**
   * {@inheritdoc}
   */
  public function form($mapping, $target, $form, $form_state) {
    return array(
      'term_search' => array(
        '#type' => 'select',
        '#title' => t('Search taxonomy terms by'),
        '#options' => $this->callbackOptions(),
        '#default_value' => !empty($mapping['term_search']) ? $mapping['term_search'] : FEEDS_TAXONOMY_SEARCH_TERM_NAME,
      ),
      'autocreate' => array(
        '#type' => 'checkbox',
        '#title' => t('Auto create'),
        '#description' => t("Create the term if it doesn't exist."),
        '#default_value' => !empty($mapping['autocreate']) ? $mapping['autocreate'] : 0,
        '#states' => array(
          'visible' => array(
            ':input[name$="[settings][term_search]"]' => array('value' => FEEDS_TAXONOMY_SEARCH_TERM_NAME),
          ),
        ),
      ),
    );
  }

  /**
   * Returns the options list.
   *
   * @return array
   *   An array of options.
   */
  protected function callbackOptions() {
    return array(
      FEEDS_TAXONOMY_SEARCH_TERM_NAME => 'Term name',
      FEEDS_TAXONOMY_SEARCH_TERM_ID => 'Term ID',
      FEEDS_TAXONOMY_SEARCH_TERM_GUID => 'GUID',
    );
  }

}
