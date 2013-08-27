<?php

/**
 * @file
 * Contains \Drupal\feeds\TargetMapper\File.
 */

namespace Drupal\feeds\TargetMapper;

use Drupal\feeds\FeedsEnclosure;

/**
 *
 */
class File extends FieldTargetMapperBase {

  public function setTarget(EntityInterface $entity, $field_name, array $values) {
    $field = $entity->get($field_name);

    $new_values = array();

    foreach ($values as $delta => $value) {

      $new_values[$delta] = $this->initialFieldValues();

      foreach ($value as $column => $column_value) {

        switch ($column) {
          case 'alt':
          case 'title':
            return parent::validate($entity, $column, $value);

          case 'uri':

          try {
            $column_value = $this->validate($entity, $column, $column_value);
            $new_values[$delta][$key] = $column_value;
          }
          catch (ValidationException $e) {

          }
        }
      }
    }

    $field->setValue($new_values);
  }

  /**
   * {@inheritdoc}
   */
  protected function validate(EntityInterface $entity, $column, $value) {
    switch ($column) {
      case 'alt':
      case 'title':
        return parent::validate($entity, $column, $value);

      case 'uri':
        $data = array();
        if (!empty($entity->uid)) {
          $data[$entity->entityType()] = $entity;
        }
        $destination = file_field_widget_uri($this->instance->getFieldSettings(), $data);

        try {
          if (!($value instanceof FeedsEnclosure)) {
            if (is_string($value)) {
              $value = new FeedsEnclosure($value, file_get_mimetype($value));
            }
            else {
              return '';
            }
          }

          $file = $value->getFile($destination);

          $this->defaults = array(
            'entity' => $file,
            'target_id' => $file->id(),
            'display' => 1,
          );
        }
        catch (Exception $e) {
          watchdog_exception('Feeds', $e, nl2br(check_plain($e)));
          return FALSE;
        }
        return NULL;
    }
  }

  protected function defaults() {
    return $this->defaults;
  }

}
