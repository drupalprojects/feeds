<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Target\Taxonomy.
 */

namespace Drupal\feeds\Plugin\feeds\Target;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines a taxonomy field mapper.
 *
 * @Plugin(
 *   id = "taxonomy",
 *   title = @Translation("Taxonomy"),
 *   field_types = {"taxonomy_term_reference"}
 * )
 */
class Taxonomy extends EntityReference {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  protected function getEntityType() {
    return 'taxonomy_term';
  }

  protected function prepareTarget(array &$target) {
    unset($target['properties']['revision_id']);
  }

  public function prepareValues(array &$values) {
    foreach ($values as $delta => $columns) {
      foreach ($columns as $column => $value) {
        switch ($column) {
          case 'target_id':
            $values[$delta][$column] = $this->getTermId($value);
            break;
        }
      }
    }
  }

  /**
   * Returns a taxonomy term id.
   */
  protected function getTermId($value) {
    if ($values = $this->getByLabel($value)) {
      return reset($values);
    }
    $settings = $this->configuration['instance']->getFieldSettings();
    $term = \Drupal::EntityManager()->getStorageController('taxonomy_term')->create(array('vid' => 'tags', 'name' => $value));
    $term->save();
    return $term->id();
  }

  protected function getDefaultConfiguration() {
    return array('instance' => NULL) + parent::getDefaultConfiguration();
  }

}
