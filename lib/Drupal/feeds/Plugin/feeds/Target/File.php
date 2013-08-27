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

  /**
   * {@inheritdoc}
   */
  protected function applyTargets(FieldInstance $instance) {
    $targets = array();

    $targets[$instance->getFieldName()] = array(
      'name' => t('@label: URI', array('@label' => $instance->label())),
      'callback' => array($this, 'setTarget'),
      'description' => t('The URI of the @label field.', array('@label' => $instance->label())),
      'columns' => array('uri'),
    );

    if ($instance->getFieldType() == 'image') {
      $targets[$instance->getFieldName()]['columns'][] = 'title';
      $targets[$instance->getFieldName()]['columns'][] = 'alt';
    }

    return $targets;
  }

  /**
   * {@inheritdoc}
   */
  protected function validate($key, $value, array $mapping) {
    switch ($key) {
      case 'alt':
      case 'title':
        return parent::validate($key, $value, $mapping);

      case 'uri':
        $data = array();
        if (!empty($this->entity->uid)) {
          $data[$entity->entityType()] = $this->entity;
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
