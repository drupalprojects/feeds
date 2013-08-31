<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Target\EntityReference.
 */

namespace Drupal\feeds\Plugin\feeds\Target;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\feeds\Plugin\FieldTargetBase;

/**
 * Defines an entity reference field mapper.
 *
 * @Plugin(
 *   id = "entity_reference",
 *   title = @Translation("EntityReference"),
 *   field_types = {"entity_reference_field"}
 * )
 */
class EntityReference extends FieldTargetBase {

  /**
   * Constructs a ConfigurablePluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityType = $this->getEntityType();
    $info = \Drupal::entityManager()->getDefinition($this->entityType);
    $this->bundleKey = FALSE;
    if (!empty($info['bundle_keys'])) {
      $this->bundleKey = $info['bundle_keys']['bundle'];
    }
    $this->entityKeys = $info['entity_keys'];
  }

  protected function getEntityType() {
    return $this->configuration['settings']['target_type'];
  }

  protected function prepareTarget(array &$target) {
  }

  protected function getByLabel($value) {
    $query = \Drupal::entityQuery($this->entityType);

    if ($this->bundle) {
      $query->condition($this->bundleKey, $this->bundle);
    }

    return $query->condition($this->entityKeys['label'], $value)->range(0, 1)->execute();
  }

  protected function getDefaultConfiguration() {
    return array('settings' => array('target_type' => 'user'));
  }

}
