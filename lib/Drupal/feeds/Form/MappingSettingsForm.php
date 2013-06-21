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
      '#submit' => array('feeds_ui_mapping_form_multistep_submit'),
      '#ajax' => array(
        'callback' => 'feeds_ui_mapping_settings_form_callback',
        'wrapper' => 'feeds-ui-mapping-form-wrapper',
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
      $settings_form += feeds_ui_mapping_settings_optional_unique_form($this->mapping, $this->target, $form, $form_state);

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
      if ($optional_unique_summary = feeds_ui_mapping_settings_optional_unique_summary($this->mapping, $this->target, $form, $form_state)) {
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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {}

}
