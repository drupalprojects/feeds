<?php

/**
 * @file
 * Contains \Drupal\feeds\Form\MappingSettingsForm.
 */

namespace Drupal\feeds\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\feeds\ImporterInterface;

/**
 * Provides a form for an individual mapping's settings.
 */
class MappingSettingsForm implements FormInterface {

  /**
   * The position in the mapping array that is being configured.
   *
   * @var int
   */
  protected $delta;

  /**
   * The individual mapping array.
   *
   * @var array
   */
  protected $mapping;

  /**
   * The target being configured.
   *
   * @var array
   */
  protected $target;

  /**
   * Constructs a new MappingSettingsForm object.
   *
   * @param int $delta
   *   The position in the mapping array.
   * @param array $mapping
   *   The mapping array.
   * @param array $target
   *   The target configuration.
   */
  public function __construct($delta, array $mapping, array $target) {
    $this->delta = $delta;
    $this->mapping = $mapping;
    $this->target = $target;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'feeds_mapping_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {

    // Apply defaults to $form_state.
    $form_state += array(
      'mapping_settings_edit' => NULL,
      'mapping_settings' => array(),
    );

    $base_button = array(
      '#submit' => array(array($this, 'submitForm')),
      '#ajax' => array(
        'callback' => array($this, 'ajaxCallback'),
        'wrapper' => 'feeds-mapping-form-wrapper',
        'effect' => 'fade',
        'progress' => 'none',
      ),
      '#delta' => $this->delta,
    );

    // Find the individual mapping we are working with.
    if (isset($form_state['mapping_settings'][$this->delta])) {
      $this->mapping = $form_state['mapping_settings'][$this->delta] + $this->mapping;
    }

    // We are in edit mode, present a form.
    if ($form_state['mapping_settings_edit'] === $this->delta) {
      // Build the form.
      if (isset($this->target['form_callback'])) {
        $settings_form = call_user_func($this->target['form_callback'], $this->mapping, $this->target, $form, $form_state);
      }
      else {
        $settings_form = array();
      }

      // Merge in the optional unique form.
      // @todo Move this to a read API.
      $settings_form += $this->optionalUniqueForm($this->mapping, $this->target, $form, $form_state);

      // Return the form.
      return array(
        '#type' => 'container',
        'settings' => $settings_form,
        'save_settings' => $base_button + array(
          '#type' => 'submit',
          '#name' => 'mapping_settings_update_' . $this->delta,
          '#value' => t('Update'),
          '#op' => 'update',
        ),
        'cancel_settings' => $base_button + array(
          '#type' => 'submit',
          '#name' => 'mapping_settings_cancel_' . $this->delta,
          '#value' => t('Cancel'),
          '#op' => 'cancel',
        ),
      );
    }
    else {
      // Build the summary.
      if (isset($this->target['summary_callback'])) {
        $summary = call_user_func($this->target['summary_callback'], $this->mapping, $this->target, $form, $form_state);
      }
      else {
        $summary = '';
      }

      // Append the optional unique summary.
      // @todo Move this to an API.
      if ($optional_unique_summary = $this->optionalUniqueSummary($this->mapping, $this->target, $form, $form_state)) {
        $summary .= ' ' . $optional_unique_summary;
      }

      if ($summary) {
        // Return the summary form.
        return array(
          'summary' => array(
            '#prefix' => '<div>',
            '#markup' => $summary,
            '#suffix' => '</div>',
          ),
          'edit_settings' => $base_button + array(
            '#type' => 'image_button',
            '#name' => 'mapping_settings_edit_' . $this->delta,
            '#attributes' => array('alt' => t('Edit')),
            '#op' => 'edit',
            '#src' => 'core/misc/configure-dark.png',
          ),
        );
      }
    }
  }

  /**
   * Provides an optional unique checkbox.
   *
   * @param array $mapping
   *   The mapping configuration.
   * @param array $target
   *   The target configuration.
   * @param array $form
   *   The form.
   * @param array $form_state
   *   The form state.
   *
   * @return array
   *   Return the optional_unique settings form.
   *
   * @todo Make this a better API.
   */
  protected function optionalUniqueForm(array $mapping, array $target, array $form, array $form_state) {
    $settings_form = array();

    if (!empty($target['optional_unique'])) {
      $settings_form['unique'] = array(
        '#type' => 'checkbox',
        '#title' => t('Unique'),
        '#default_value' => !empty($mapping['unique']),
      );
    }

    return $settings_form;
  }

  /**
   * Shows whether a mapping is used as unique or not per mapping.
   *
   * @param array $mapping
   *   The mapping configuration.
   * @param array $target
   *   The target configuration.
   * @param array $form
   *   The form.
   * @param array $form_state
   *   The form state.
   *
   * @return array
   *   Return the optional_unique settings form.
   *
   * @todo Make this a better API.
   */
  protected function optionalUniqueSummary(array $mapping, array $target, array $form, array $form_state) {
    if (!empty($target['optional_unique'])) {
      if ($mapping['unique']) {
        return t('Used as <strong>unique</strong>.');
      }
      else {
        return t('Not used as unique.');
      }
    }
  }

  /**
   * Ajax callback.
   */
  public function ajaxCallback(array $form, array &$form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $trigger = $form_state['triggering_element'];
    $delta = $trigger['#delta'];

    switch ($trigger['#op']) {
      case 'edit':
        $form_state['mapping_settings_edit'] = $delta;
        break;

      case 'update':
        $values = $form_state['values']['config'][$delta]['settings'];
        $form_state['mapping_settings'][$delta] = $values;
        unset($form_state['mapping_settings_edit']);
        break;

      case 'cancel':
        unset($form_state['mapping_settings_edit']);
        break;
    }

    $form_state['rebuild'] = TRUE;
  }

}
