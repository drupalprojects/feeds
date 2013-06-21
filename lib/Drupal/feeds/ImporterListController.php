<?php

/**
 * @file
 * Contains \Drupal\feeds\ImporterListController.
 */

namespace Drupal\feeds;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityListController;

/**
 * Provides a listing of Importers.
 */
class ImporterListController extends ConfigEntityListController {

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['description'] = $entity->description;
    $row['operations']['data'] = $this->buildOperations($entity);
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $row['label'] = t('Label');
    $row['id'] = t('Machine name');
    $row['description'] = t('Description');
    $row['operations'] = t('Operations');
    return $row;
  }

}
