<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Target\File.
 */

namespace Drupal\feeds\Plugin\feeds\Target;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Language\Language;
use Drupal\feeds\FeedsEnclosure;
use Drupal\feeds\Plugin\FieldTargetBase;
use Drupal\field\Entity\FieldInstance;

/**
 * Defines a file field mapper.
 *
 * @Plugin(
 *   id = "file",
 *   title = @Translation("File"),
 *   field_types = {"file", "image"}
 * )
 */
class File extends FieldTargetBase {

  protected $instance;

  protected function prepareTarget(array &$target) {
    unset($target['properties']['revision_id']);
    $this->instance = $target['instance'];
  }

  public function prepareValues(array &$values) {
    foreach ($values as $delta => $columns) {
      foreach ($columns as $column => $value) {
        switch ($column) {
          case 'alt':
          case 'title':
            $values[$delta][$column] = (string) $value;
            break;

          case 'width':
          case 'height':
            $values[$delta][$column] = (int) $value;
            break;

          case 'target_id':
            $values[$delta][$column] = $this->getFile($value);
            break;

          case 'display':
            $values[$delta][$column] = 1;
        }
      }
    }
  }

  protected function getFile($value) {
    $data = array();
    // $destination = file_field_widget_uri($this->instance->getFieldSettings(), $data);
    $destination = 'public://';

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
      return $file->id();
    }
    catch (Exception $e) {
      watchdog_exception('Feeds', $e, nl2br(check_plain($e)));
    }
  }

}
