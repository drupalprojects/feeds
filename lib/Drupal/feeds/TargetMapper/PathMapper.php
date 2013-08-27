<?php

/**
 * @file
 * Contains \Drupal\feeds\TargetMapper\PathMapper.
 */

namespace Drupal\feeds\TargetMapper;

use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\FeedInterface;

/**
 * Defines a path field mapper.
 */
class PathMapper extends TargetMapperBase {

  /**
   * {@inheritdoc}
   */
  function setTarget(EntityInterface $entity, $field_name, $values) {
    $value = '';
    foreach ($values as $value) {
      if (strlen(trim($value['value']))) {
        $value = $value['value'];
        break;
      }
    }

    $entity->path = array();

    if ($entity->id()) {
      $uri = $entity->uri();

      // Check for existing aliases.
      if ($path = path_load($uri['path'])) {
        $entity->path = $path;
      }
    }

    $entity->path['pathauto'] = FALSE;
    // Allow pathauto to set the path alias if the option is set, and this value
    // is empty.
    if ($this->getConfiguration('pathauto_override') && !$value) {
      $entity->path['pathauto'] = TRUE;
    }
    else {
      $entity->path['alias'] = ltrim($value, '/');
    }
  }

  /**
   * {inheritdoc}
   */
  protected function getDefaultConfiguration() {
    return array('pathauto_override' => FALSE);
  }

}
