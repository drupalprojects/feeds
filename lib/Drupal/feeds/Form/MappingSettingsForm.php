<?php

/**
 * @file
 * Contains \Drupal\feeds\Form\MappingSettingsForm.
 */

namespace Drupal\feeds\Form;

use Drupal\Core\Form\BaseFormIdInterface;
use Drupal\feeds\ImporterInterface;

/**
 * Provides a form for mapping.
 */
class MappingSettingsForm implements BaseFormIdInterface {


  protected $i;
  protected $mapping;
  protected $target;

  /**
   * Constructs a new MappingSettingsForm object.
   *
   * @param \Drupal\feeds\ImporterInterface $importer
   *   The feeds importer.
   * @param string $plugin_type
   *   The plugin type.
   */
  public function __construct($i, $mapping, $target) {
    $this->i = $i;
    $this->mapping = $mapping;
    $this->target = $target;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormID() {
    return 'feeds_mapping_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'feeds_' . $this->target . '_mapping_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
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
      '#i' => $this->i,
    );

    if (isset($form_state['mapping_settings'][$this->i])) {
      $this->mapping = $form_state['mapping_settings'][$this->i] + $this->mapping;
    }

    if ($form_state['mapping_settings_edit'] === $this->i) {
      // Build the form.
      if (isset($this->target['form_callback'])) {
        $settings_form = call_user_func($this->target['form_callback'], $this->mapping, $this->target, $form, $form_state);
      }
      else {
        $settings_form = array();
      }

      // Merge in the optional unique form.
      $settings_form += $this->optionalUniqueForm($this->mapping, $this->target, $form, $form_state);

      return array(
        '#type' => 'container',
        'settings' => $settings_form,
        'save_settings' => $base_button + array(
          '#type' => 'submit',
          '#name' => 'mapping_settings_update_' . $this->i,
          '#value' => t('Update'),
          '#op' => 'update',
        ),
        'cancel_settings' => $base_button + array(
          '#type' => 'submit',
          '#name' => 'mapping_settings_cancel_' . $this->i,
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
      if ($optional_unique_summary = $this->optionalUniqueSummary($this->mapping, $this->target, $form, $form_state)) {
        $summary .= ' ' . $optional_unique_summary;
      }

      if ($summary) {
        return array(
          'summary' => array(
            '#prefix' => '<div>',
            '#markup' => $summary,
            '#suffix' => '</div>',
          ),
         'edit_settings' => $base_button + array(
            '#type' => 'image_button',
            '#name' => 'mapping_settings_edit_' . $this->i,
            '#src' => 'misc/configure.png',
            '#attributes' => array('alt' => t('Edit')),
            '#op' => 'edit',
            '#src' => 'core/misc/configure-dark.png',
          ),
        );
      }
    }
  }

  protected function optionalUniqueForm($mapping, $target, $form, $form_state) {
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
   * Per mapping settings summary callback. Shows whether a mapping is used as
   * unique or not.
   */
  protected function optionalUniqueSummary($mapping, $target, $form, $form_state) {
    if (!empty($target['optional_unique'])) {
      if ($mapping['unique']) {
        return t('Used as <strong>unique</strong>.');
      }
      else {
        return t('Not used as unique.');
      }
    }
  }

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

    switch ($trigger['#op']) {
      case 'edit':
        $form_state['mapping_settings_edit'] = $trigger['#i'];
        break;

      case 'update':
        $values = $form_state['values']['config'][$trigger['#i']]['settings'];
        $form_state['mapping_settings'][$trigger['#i']] = $values;
        unset($form_state['mapping_settings_edit']);
        break;

      case 'cancel':
        unset($form_state['mapping_settings_edit']);
        break;
    }

    $form_state['rebuild'] = TRUE;
  }

}
