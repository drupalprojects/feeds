<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Target\Text.
 */

namespace Drupal\feeds\Plugin\feeds\Target;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\feeds\FeedsElement;
use Drupal\field\Entity\FieldInstance;
use Drupal\feeds\Plugin\FieldTargetBase;

/**
 * Defines a text field mapper.
 *
 * @Plugin(
 *   id = "text",
 *   title = @Translation("Text")
 * )
 */
class Text extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  protected $fieldTypes = array(
    'list_text',
    'text',
    'text_long',
    'text_with_summary',
  );

  /**
   * {@inheritdoc}
   */
  protected function applyTargets(FieldInstance $instance) {
    return array(
      $instance->getFieldName() => array(
        'name' => check_plain($instance->label()),
        'callback' => array($this, 'setTarget'),
        'description' => t('The @label field of the entity.', array('@label' => $instance->label())),
      ),
    );
  }

  protected function defaults() {
    $defaults = array();
    if (isset($this->importer->getProcessor()->config['input_format'])) {
      $defaults['format'] = $this->importer->getProcessor()->config['input_format'];
    }
    return $defaults;
  }

}
