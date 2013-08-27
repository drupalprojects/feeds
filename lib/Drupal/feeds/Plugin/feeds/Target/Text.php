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
 *   title = @Translation("Text"),
 *   field_types = {"list_text", "text", "text_long", "text_with_summary"}
 * )
 */
class Text extends FieldTargetBase {

  /**
   * The input format to use for text fields.
   *
   * @var string
   */
  protected $inputFormat;

  /**
   * The detault configuration to apply to an individual text field.
   *
   * @var array
   */
  protected $defaultFieldValues;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->inputFormat = $this->importer->getProcessor()->getConfiguration('input_format');

    // Fallback to a safe choice if something went haywire.
    if (!$this->inputFormat) {
      $this->inputFormat = 'plain_text';
    }

    $this->defaultFieldValues = array('format' => $this->inputFormat);

  }

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

  protected function defaultFieldValuess() {
    return $this->defaultFieldValues;
  }

}
