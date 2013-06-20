<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Mapper\Taxonomy.
 */

namespace Drupal\feeds\Plugin\feeds\Mapper;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\FeedsElement;
use Drupal\feeds\Plugin\MapperBase;
use Drupal\feeds\Plugin\Core\Entity\Feed;
use Drupal\feeds\Plugin\Core\Entity\Importer;
use Drupal\feeds\FeedsParserResult;
use Drupal\feeds\FeedsTermElement;
use Drupal\taxonomy\Type\TaxonomyTermReferenceItem;

/**
 * Search by term name.
 */
define('FEEDS_TAXONOMY_SEARCH_TERM_NAME', 0);

/**
 * Search by term id.
 */
define('FEEDS_TAXONOMY_SEARCH_TERM_ID', 1);

/**
 * Search by GUID.
 */
define('FEEDS_TAXONOMY_SEARCH_TERM_GUID', 2);

/**
 * Defines a taxonomy field mapper.
 *
 * @Plugin(
 *   id = "taxonomy",
 *   title = @Translation("Taxonomy")
 * )
 */
class Taxonomy extends MapperBase {

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
  public function getSource(Feed $feed, FeedsParserResult $result, $key) {
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
  public function targets(array &$targets, $entity_type, $bundle) {
    foreach (field_info_instances($entity_type, $bundle) as $name => $instance) {
      $info = field_info_field($name);
      if ($info['type'] == 'taxonomy_term_reference') {
        $targets[$name] = array(
          'name' => check_plain($instance['label']),
          'callback' => array($this, 'setTarget'),
          'description' => t('The @label field of the entity.', array('@label' => $instance['label'])),
          'summary_callback' => array($this, 'summary'),
          'form_callback' => array($this, 'form'),
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  function setTarget(Feed $feed, EntityInterface $entity, $target, $terms, array $mapping) {

    // Allow mapping the string '0' to a term name.
    if (empty($terms) && $terms != 0) {
      return;
    }

    // Handle non-multiple values.
    if (!is_array($terms)) {
      $terms = array($terms);
    }

    // Add in default values.
    $mapping += array(
      'term_search' => FEEDS_TAXONOMY_SEARCH_TERM_NAME,
      'autocreate' => FALSE,
    );

    $info = field_info_field($target);

    $cache = &drupal_static(__FUNCTION__);
    if (!isset($cache['allowed_values'][$target])) {
      $instance = field_info_instance($entity->entityType(), $target, $entity->bundle());
      $cache['allowed_values'][$target] = taxonomy_allowed_values($instance, $entity);
    }

    if (!isset($cache['allowed_vocabularies'][$target])) {
      foreach ($info['settings']['allowed_values'] as $tree) {
        if ($vocabulary = entity_load('taxonomy_vocabulary', $tree['vocabulary'])) {
          $cache['allowed_vocabularies'][$target][$vocabulary->id()] = $vocabulary->id();
        }
      }
    }

    $query = \Drupal::entityQuery('taxonomy_term');
    $query
      ->condition('vid', $cache['allowed_vocabularies'][$target])
      ->range(0, 1);


    $field = isset($entity->$target) ? $entity->$target : array('und' => array());

    // Allow for multiple mappings to the same target.
    $delta = count($field['und']);

    // Iterate over all values.
    foreach ($terms as $term) {

      if ($delta >= $info['cardinality'] && $info['cardinality'] > -1) {
        break;
      }

      $tid = FALSE;

      // FeedsTermElement already is a term.
      if ($term instanceof FeedsTermElement) {
        $tid = $term->tid;
      }
      elseif ($term instanceof TaxonomyTermReferenceItem) {
        $tid = $term->value;
      }
      else {
        switch ($mapping['term_search']) {

          // Lookup by name.
          case FEEDS_TAXONOMY_SEARCH_TERM_NAME:
            $name_query = clone $query;
            if ($tids = $name_query->condition('name', $term)->execute()) {
              $tid = key($tids);
            }
            elseif ($mapping['autocreate']) {
              $term = entity_create('taxonomy_term', array(
                'name' => $term,
                'vid' => key($cache['allowed_vocabularies'][$target]),
              ));
              $term->save();
              $tid = $term->id();
              // Add to the list of allowed values.
              $cache['allowed_values'][$target][$tid] = $term->label();;
            }
            break;

          // Lookup by tid.
          case FEEDS_TAXONOMY_SEARCH_TERM_ID:
            if (is_numeric($term)) {
              $tid = $term;
            }
            break;

          // Lookup by GUID.
          case FEEDS_TAXONOMY_SEARCH_TERM_GUID:
            $tid = $this->getTermByGUID($term);
            break;
        }
      }

      if ($tid && isset($cache['allowed_values'][$target][$tid])) {
        $field['und'][$delta]['target_id'] = $tid;
        $delta++;
      }
    }

    $entity->$target = $field;
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
