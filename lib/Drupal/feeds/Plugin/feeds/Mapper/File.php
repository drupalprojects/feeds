<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Mapper\File.
 */

namespace Drupal\feeds\Plugin\feeds\Mapper;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Language\Language;
use Drupal\feeds\FeedsEnclosure;
use Drupal\feeds\Plugin\FieldMapperBase;
use Drupal\field\Plugin\Core\Entity\FieldInstance;

/**
 * Defines a file field mapper.
 *
 * @Plugin(
 *   id = "file",
 *   title = @Translation("File")
 * )
 */
class File extends FieldMapperBase {

  /**
   * {@inheritdoc}
   */
  protected $fieldTypes = array('file', 'image');

  /**
   * {@inheritdoc}
   */
  protected function applyTargets(array &$targets, FieldInstance $instance) {
    $targets[$instance->getFieldName() . ':uri'] = array(
      'name' => t('@label: URI', array('@label' => $instance->label())),
      'callback' => array($this, 'setTarget'),
      'description' => t('The URI of the @label field.', array('@label' => $instance->label())),
      'real_target' => $instance->getFieldName(),
    );

    if ($instance->getFieldType() == 'image') {
      $targets[$instance->getFieldName() . ':alt'] = array(
        'name' => t('@label: Alt', array('@label' => $instance->label())),
        'callback' => array($this, 'setTarget'),
        'description' => t('The alt tag of the @label field.', array('@label' => $instance->label())),
        'real_target' => $instance->getFieldName(),
      );
      $targets[$instance->getFieldName() . ':title'] = array(
        'name' => t('@label: Title', array('@label' => $instance->label())),
        'callback' => array($this, 'setTarget'),
        'description' => t('The title of the @label field.', array('@label' => $instance->label())),
        'real_target' => $instance->getFieldName(),
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function buildField(array $field, $column, array $values, array $mapping) {

    if ($column == 'uri') {
      foreach ($values as $k => $value) {
        if (!($value instanceof FeedsEnclosure)) {
          if (is_string($value)) {
            $values[$k] = new FeedsEnclosure($value, file_get_mimetype($value));
          }
          else {
            unset($values[$k]);
          }
        }
      }
      if (!$values) {
        return;
      }

      static $destination;
      if (!$destination) {
        // Determine file destination.
        // @todo This needs review and debugging.
        $data = array();
        if (!empty($this->entity->uid)) {
          $data[$entity_type] = $this->entity;
        }
        $destination = file_field_widget_uri($this->instance->getFieldSettings(), $data);
      }
    }

    $delta = 0;
    foreach ($values as $value) {
      if ($delta >= $this->cardinality) {
        break;
      }

      if (!isset($field[Language::LANGCODE_NOT_SPECIFIED][$delta])) {
        $field[Language::LANGCODE_NOT_SPECIFIED][$delta] = array();
      }

      switch ($column) {
        case 'alt':
        case 'title':
          $field[Language::LANGCODE_NOT_SPECIFIED][$delta][$column] = $value;
          break;

        case 'uri':
          try {
            $file = $value->getFile($destination);
            $field[Language::LANGCODE_NOT_SPECIFIED][$delta]['entity'] = $file;
            $field[Language::LANGCODE_NOT_SPECIFIED][$delta]['fid'] = $file->id();
            // $field[Language::LANGCODE_NOT_SPECIFIED][$delta]['description'] = $file->description->value;
            // @todo: Figure out how to properly populate this field.
            $field[Language::LANGCODE_NOT_SPECIFIED][$delta]['display'] = 1;
          }
          catch (Exception $e) {
            watchdog_exception('Feeds', $e, nl2br(check_plain($e)));
          }
          break;
      }

      $delta++;
    }

    return $field;
  }

}
