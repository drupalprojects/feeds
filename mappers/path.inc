<?php

/**
 * @file
 * On behalf implementation of Feeds mapping API for path.module.
 */

/**
 * Implements hook_feeds_processor_targets_alter().
 *
 * @see FeedsNodeProcessor::getMappingTargets().
 */
function path_feeds_processor_targets_alter(&$targets, $entity_type, $bundle_name) {
  if (module_exists('path')) {

    switch ($entity_type) {
      case 'node':
      case 'taxonomy_term':
        $targets['path_alias'] = array(
          'name' => t('Path alias'),
          'description' => t('URL path alias of the node.'),
          'callback' => 'path_feeds_set_target',
        );
        break;
    }
  }
}

/**
 * Callback for mapping. Here is where the actual mapping happens.
 *
 * When the callback is invoked, $target contains the name of the field the
 * user has decided to map to and $value contains the value of the feed item
 * element the user has picked as a source.
 */
function path_feeds_set_target($source, $entity, $target, $value) {

  // Path alias cannot be multi-valued, so use the first value.
  if (is_array($value) && $value) {
    $value = $value[0];
  }

  $entity->path = array();

  $entity_type = $source->importer->processor->entityType();

  list($id, , ) = entity_extract_ids($entity_type, $entity);

  if ($id) {
    $uri = entity_uri($entity_type, $entity);

    // Check for existing aliases.
    if ($path = path_load($uri['path'])) {
      $entity->path = $path;
    }
  }

  // Prevent Pathauto (http://drupal.org/project/pathauto) from overwriting the 
  // alias.
  $entity->path['pathauto'] = FALSE;

  $entity->path['alias'] = ltrim($value, '/');
}
