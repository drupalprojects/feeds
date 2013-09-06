<?php

/**
 * @file
 * Contains \Drupal\feeds\Source\FieldSourceBase.
 */

namespace Drupal\feeds\Plugin\Type\Source;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\ImporterInterface;
use Drupal\feeds\Plugin\Type\PluginBase;
use Drupal\feeds\Plugin\Type\Source\SourceInterface;

/**
 * Returns the fields of a feed as sources.
 */
abstract class FieldSourceBase extends PluginBase implements SourceInterface {

  /**
   * {@inheritdoc}
   */
  public static function sources(array &$sources, ImporterInterface $importer, array $definition) {
    $field_definitions = \Drupal::entityManager()->getFieldDefinitions('feeds_feed', $importer->id());

    foreach ($field_definitions as $field => $field_definition) {
      if (in_array($field_definition['type'], $definition['field_types'])) {
        $field_definition['label'] = t('Feed: @label', array('@label' => $field_definition['label']));
        $sources['parent:' . $field] = $field_definition;
        $sources['parent:' . $field]['id'] = $definition['id'];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceElement(FeedInterface $feed, array $item, $element_key) {
    $return = array();
    if ($field = $feed->get($element_key)) {
      foreach ($field->getValue() as $values) {
        foreach ($values as $value) {
          $return[] = $value['value'];
        }
      }
    }

    return $return;
  }

}
