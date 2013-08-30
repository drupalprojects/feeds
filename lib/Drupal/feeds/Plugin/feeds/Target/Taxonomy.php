<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Target\Taxonomy.
 */

namespace Drupal\feeds\Plugin\feeds\Target;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\feeds\Plugin\FieldTargetBase;

/**
 * Defines a taxonomy field mapper.
 *
 * @Plugin(
 *   id = "taxonomy",
 *   title = @Translation("Taxonomy"),
 *   field_types = {"taxonomy_term_reference"}
 * )
 */
class Taxonomy extends FieldTargetBase {

  protected function prepareTarget(array &$target) {
    unset($target['properties']['revision_id']);
  }

  public function prepareValues(array &$values) {
    foreach ($values as $delta => $columns) {
      foreach ($columns as $column => $value) {
        switch ($column) {
          case 'target_id':
            $values[$delta][$column] = $this->getTerm($value);
            break;
        }
      }
    }
  }

  protected function getTerm($value) {
    $term = \Drupal::EntityManager()->getStorageController('taxonomy_term')->create(array('vid' => 'tags', 'name' => $value));
    $term->save();
    return $term->id();
  }

}
