<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Processor\NodeProcessor.
 */

namespace Drupal\feeds\Feeds\Processor;

/**
 * Defines a node processor.
 *
 * Creates nodes from feed items.
 *
 * @FeedsProcessor(
 *   id = "entity:node",
 *   title = @Translation("Node"),
 *   description = @Translation("Creates nodes from feed items."),
 *   entity_type = "node",
 *   arguments = {"@entity.manager", "@entity.query"}
 * )
 */
class NodeProcessor extends EntityProcessorBase {

  /**
   * {@inheritdoc}
   */
  protected function entityLabel() {
    return $this->t('Node');
  }

  /**
   * {@inheritdoc}
   */
  protected function entityLabelPlural() {
    return $this->t('Nodes');
  }

}
