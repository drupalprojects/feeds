<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Mapper\Path.
 */

namespace Drupal\feeds\Plugin\feeds\Mapper;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\FeedsElement;
use Drupal\feeds\Plugin\MapperBase;
use Drupal\feeds\Plugin\Core\Entity\Feed;

/**
 * Defines a path field mapper.
 *
 * @Plugin(
 *   id = "path",
 *   title = @Translation("Path")
 * )
 */
class Path extends MapperBase {

  /**
   * {@inheritdoc}
   */
  public function targets(array &$targets, $entity_type, $bundle) {
    switch ($entity_type) {
      case 'node':
      case 'taxonomy_term':
      case 'user':
        $targets['path_alias'] = array(
          'name' => t('Path alias'),
          'description' => t('URL path alias of the node.'),
          'callback' => array($this, 'setTarget'),
          'summary_callback' => array($this, 'summary'),
          'form_callback' => array($this, 'form'),
        );
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  function setTarget(Feed $feed, EntityInterface $entity, $target, $value) {
    if (empty($value)) {
      $value = '';
    }

    // Path alias cannot be multi-valued, so use the first value.
    if (is_array($value)) {
      $value = $value[0];
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
    if (!empty($mapping['pathauto_override']) && !$value) {
      $entity->path['pathauto'] = TRUE;
    }
    else {
      $entity->path['alias'] = ltrim($value, '/');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function summary($mapping, $target, $form, $form_state) {
    if (!module_exists('pathauto')) {
      return;
    }

    if (empty($mapping['pathauto_override'])) {
      return t('Do not allow Pathauto if empty.');
    }

    else {
      return t('Allow Pathauto if empty.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form($mapping, $target, $form, $form_state) {
    return array(
      'pathauto_override' => array(
        '#type' => 'checkbox',
        '#title' => t('Allow Pathauto to set the alias if the value is empty.'),
        '#default_value' => !empty($mapping['pathauto_override']),
      ),
    );
  }

}
