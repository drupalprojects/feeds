<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\Derivative\EntityProcessor.
 */

namespace Drupal\feeds\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DerivativeInterface;

/**
 * Provides processor definitions for entities.
 *
 * @see \Drupal\feeds\Plugin\feeds\Processor\EntityProcessor
 */
class EntityProcessor implements DerivativeInterface {

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
    if (!empty($this->derivatives) && !empty($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }

    $this->getDerivativeDefinitions($base_plugin_definition);

    return $this->derivatives[$derivative_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    foreach (entity_get_info() as $entity_type => $entity_info) {
      $this->derivatives[$entity_type] = $base_plugin_definition;
      $this->derivatives[$entity_type]['title'] = $entity_info['label'];
      $this->derivatives[$entity_type]['entity type'] = $entity_type;
    }

    return $this->derivatives;
  }

}
