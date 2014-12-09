<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\views\field\FeedType.
 */

namespace Drupal\feeds\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ResultRow;

/**
 * Field handler to translate a feed feed type into its readable form.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("feeds_feed_type")
 */
class FeedType extends Feed {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['machine_name'] = ['default' => FALSE, 'bool' => TRUE];

    return $options;
  }

  /**
   * Provides the machine_name option for to feed feed type display.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['machine_name'] = [
      '#title' => t('Output machine name'),
      '#description' => t('Display field as the feed type machine name.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['machine_name']),
    ];
  }

  /**
    * Render node type as human readable name, unless using machine_name option.
    */
  function render_name($data, $values) {
    if ($this->options['machine_name'] != 1 && $data !== NULL && $data !== '') {
      $type = entity_load('feeds_feed_type', $data);
      return $type ? t($this->sanitizeValue($type->label())) : '';
    }
    return $this->sanitizeValue($data);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    return $this->renderLink($this->render_name($value, $values), $values);
  }

}
