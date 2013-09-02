<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\Derivative\FieldTarget.
 */

namespace Drupal\feeds\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DerivativeInterface;

/**
 * Provides processor definitions for entities.
 *
 * @see \Drupal\feeds\Feeds\Processor\FieldTarget
 */
class FieldTarget implements DerivativeInterface {

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = array();

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinition($derivative_id, array $base_plugin_definition) {
    if ($this->derivatives && isset($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }

    $this->getDerivativeDefinitions($base_plugin_definition);

    return $this->derivatives[$derivative_id];
  }

  /**
   * {@inheritdoc}
   *
   * @todo Do we want to limit to content entities? There's a lot in the list.
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    foreach (entity_get_info() as $entity_type => $entity_info) {
      $this->derivatives[$entity_type] = $base_plugin_definition;
      $this->derivatives[$entity_type]['title'] = $entity_info['label'];
      $this->derivatives[$entity_type]['entity type'] = $entity_type;
    }

    $this->sortDerivatives();

    return $this->derivatives;
  }

  /**
   * Sorts the derivatives based on the title.
   */
  protected function sortDerivatives() {
    uasort($this->derivatives, function($a, $b) {
      return strnatcmp($a['title'], $b['title']);
    });
  }

}
