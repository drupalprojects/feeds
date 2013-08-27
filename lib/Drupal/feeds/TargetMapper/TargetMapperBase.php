<?php

/**
 * @file
 * Contains \Drupal\feeds\TargetMapper\TargetMapperBase.
 */

abstract class TargetMapperBase implements TargetMapperInterface {

  /**
   * The Feed being imported.
   *
   * @var \Drupal\feeds\FeedInterface
   */
  protected $feed;

  /**
   * The importer the Feed belongs to.
   *
   * @var \Drupal\feeds\ImporterInterface
   */
  protected $importer;

  /**
   * The configuration for this target mapper.
   */
  protected $configuration;

  /**
   * Constructs a TargetMapperBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   */
  public function __construct(FeedInterface $feed, array $configuration) {
    $this->feed = $feed;
    $this->importer = $feed->getImporter();
    $this->configuration = $configuration;
  }

  abstract public function setTarget(EntityInterface $entity, $field_name, array $values);

}
