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
 *
 * @todo Would making this sortable help in specifying the importance of a feed?
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
    $row['label'] = $this->t('Label');
    $row['id'] = $this->t('Machine name');
    $row['description'] = $this->t('Description');
    $row['operations'] = $this->t('Operations');
    return $row;
  }

}
