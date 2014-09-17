<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\Derivative\EntityProcessor.
 */

namespace Drupal\feeds\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides processor definitions for entities.
 *
 * @see \Drupal\feeds\Feeds\Processor\EntityProcessor
 */
class EntityProcessor extends DeriverBase implements ContainerDeriverInterface {

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Constructs an EntityProcessor object.
   *
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManager $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static($container->get('entity.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->entityManager->getDefinitions() as $entity_type => $entity_info) {
      // Only show content entities for now.
      if (!$entity_info->isSubclassOf('Drupal\Core\Config\Entity\ConfigEntityInterface')) {
        $this->derivatives[$entity_type] = $base_plugin_definition;
        $this->derivatives[$entity_type]['title'] = $entity_info->getLabel();
        $this->derivatives[$entity_type]['entity type'] = $entity_type;
      }
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
