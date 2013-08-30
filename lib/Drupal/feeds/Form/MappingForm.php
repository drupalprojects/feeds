<?php

/**
 * @file
 * Contains \Drupal\feeds\Form\MappingForm.
 *
 * @todo This needs some love.
 */

namespace Drupal\feeds\Form;

use Drupal\Core\Form\FormBase;
use Drupal\feeds\ImporterInterface;

/**
 * Provides a form for mapping configuration.
 */
class MappingForm extends FormBase {

  /**
   * The feeds importer.
   *
   * @var \Drupal\feeds\ImporterInterface
   */
  protected $importer;

  /**
   * The mappings for this importer.
   *
   * @var array
   */
  protected $mappings;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'feeds_mapping_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, ImporterInterface $feeds_importer = NULL) {
    $importer = $this->importer = $feeds_importer;
    if (empty($this->mappings)) {
      $this->mappings = $importer->getMappings();
    }

    $sources = $importer->getParser()->getMappingSources();
    $this->targets = $targets = $importer->getProcessor()->getMappingTargets();

    // Denormalize targets.
    $source_options = array();
    foreach ($sources as $key => $info) {
      $source_options[$key] = $info['label'];
    }
    $source_options = $this->sortOptions($source_options);

    $target_options = array();
    foreach ($targets as $key => $info) {
      $target_options[$key] = $info['label'];
    }
    $target_options = $this->sortOptions($target_options);

    $form['#tree'] = TRUE;

    $ajax_delta = -1;
    if (isset($form_state['values'])) {
      if (isset($form_state['triggering_element']['#delta'])) {
        $delta = $form_state['triggering_element']['#delta'];
        if (empty($form_state['triggering_element']['#saved'])) {
          $ajax_delta = $delta;
        }
        else {
          $data = $form_state['values']['mappings'][$delta]['configuration'];
          $form_state['mapping_configuration'][$delta] = $data;
        }
      }

      elseif ($form_state['triggering_element']['#name'] == 'add_target' || !empty($form_state['triggering_element']['#remove'])) {
        $this->processFormState($form_state);
        drupal_set_message($this->t('Your changes will not be saved until you click the <em>Save</em> button at the bottom of the page.'), 'warning');
      }
    }

    $form['#prefix'] = '<div id="feeds-mapping-form-ajax-wrapper">';
    $form['#suffix'] = '</div>';

    $table = array(
      '#type' => 'table',
      '#header' => array(
        $this->t('Source'),
        $this->t('Target'),
        $this->t('Configure'),
        $this->t('Remove'),
      ),
      '#sticky' => TRUE,
    );

    foreach ($this->mappings as $delta => $mapping) {

      $table[$delta]['map'] = array(
        '#type' => 'container',
      );
      $table[$delta]['targets'] = array(
        '#theme' => 'item_list',
        '#items' => array(),
      );
      // Keep the target values out of the table so that the columns align.
      $form['targets'][$delta]['target'] = array(
        '#type' => 'value',
        '#value' => $mapping['target'],
        '#parents' => array('mappings', $delta, 'target'),
      );

      foreach ($mapping['map'] as $column => $source) {
        $table[$delta]['map'][$column] = array(
          '#type' => 'select',
          '#options' => $source_options,
          '#default_value' => $source,
          '#empty_option' => $this->t('- Select a source -'),
        );

        $label = check_plain($targets[$mapping['target']]['label']);

        if (count($mapping['map']) > 1) {
          $label .= ': ' . $targets[$mapping['target']]['properties'][$column]['label'];
        }

        $table[$delta]['targets']['#items'][] = $label;
      }

      $table[$delta]['configuration']['#markup'] = '';
      if (isset($targets[$mapping['target']]['target'])) {
        $table[$delta]['configuration'] = array(
          '#type' => 'container',
          '#id' => 'edit-mappings-' . $delta . '-configuration',
        );
        if ($delta == $ajax_delta) {
          $table[$delta]['configuration']['thing'] = array(
            '#type' => 'textfield',
          );
          $table[$delta]['configuration']['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Save'),
            '#ajax' => array(
              'callback' => array($this, 'configurationAjaxCallback'),
              'wrapper' => 'edit-mappings-' . $delta . '-configuration',
              'effect' => 'fade',
              'progress' => 'none',
            ),
            '#name' => 'target-save-' . $delta,
            '#delta' => $delta,
            '#saved' => TRUE,
            '#parents' => array('config_button', $delta),
          );
        }
        else {
          $plugin = $feeds_importer->getTargetPlugin($delta, $targets[$mapping['target']]['target']);
          $table[$delta]['configuration']['summary'] = array(
            '#type' => 'item',
            '#markup' => $plugin->getSummary(),
            '#parents' => array('config_summary', $delta),
          );
          $table[$delta]['configuration']['button'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Configure'),
            '#ajax' => array(
              'callback' => array($this, 'configurationAjaxCallback'),
              'wrapper' => 'edit-mappings-' . $delta . '-configuration',
              'effect' => 'fade',
              'progress' => 'none',
            ),
            '#name' => 'target-configuration-' . $delta,
            '#delta' => $delta,
            '#parents' => array('config_button', $delta),
          );
        }
      }

      $table[$delta]['remove'] = array(
        '#title' => $this->t('Remove'),
        '#type' => 'checkbox',
        '#default_value' => FALSE,
        '#title_display' => 'invisible',
        '#parents' => array('remove_mappings', $delta),
        '#ajax' => array(
          'callback' => array($this, 'ajaxCallback'),
          'wrapper' => 'feeds-mapping-form-ajax-wrapper',
          'effect' => 'none',
          'progress' => 'none',
        ),
        '#remove' => TRUE,
      );
    }

    $table['add']['source']['#markup'] = '';

    $table['add']['target'] = array(
      '#type' => 'select',
      '#title' => $this->t('Add a target'),
      '#title_display' => 'invisible',
      '#options' => $target_options,
      '#empty_option' => $this->t('- Select a target -'),
      '#parents' => array('add_target'),
      '#default_value' => NULL,
      '#ajax' => array(
        'callback' => array($this, 'ajaxCallback'),
        'wrapper' => 'feeds-mapping-form-ajax-wrapper',
        'effect' => 'none',
        'progress' => 'none',
      ),
    );

    $table['add']['configuration']['#markup'] = '';
    $table['add']['remove']['#markup'] = '';

    $form['mappings'] = $table;

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    );

    return $form;

  }

  /**
   * Processes the form state, populating the mapping array.
   *
   * @param arary &$form_state
   *   The form state array to process.
   */
  protected function processFormState(&$form_state) {
    dd($form_state['values']);
    foreach (array_keys(array_filter($form_state['values']['remove_mappings'])) as $delta) {
      unset($this->mappings[$delta]);
    }

    $new_target = $form_state['values']['add_target'];
    if ($new_target) {
      $map = array_fill_keys(array_keys($this->targets[$new_target]['properties']), '');
      $this->mappings[] = array('target' => $form_state['values']['add_target'], 'map' => $map);
    }

    $this->importer->setMappings($this->mappings);

    // Allow the #default_value of 'add_target' to be reset.
    unset($form_state['input']['add_target']);
  }

  public function validateForm(array &$form, array &$form_state) {
    if (isset($form_state['triggering_element']['#delta'])) {
      $form_state['rebuild'] = TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->mappings = $form_state['values']['mappings'];
    $this->processFormState($form_state);

    if (!empty($form_state['mapping_configuration'])) {
      foreach ($form_state['mapping_configuration'] as $delta => $configuration) {
        $this->mappings[$delta]['configuration'] = $configuration;
      }
    }

    $this->importer->setMappings($this->mappings);
    $this->importer->save();

  }

  /**
   * Builds an options list from mapping sources or targets.
   *
   * @param array $options
   *   The options to sort.
   *
   * @return array
   *   The sorted options.
   */
  protected function sortOptions(array $options) {
    $result = array();
    foreach ($options as $k => $v) {
      if (is_array($v) && !empty($v['label'])) {
        $result[$k] = $v['label'];
      }
      elseif (is_array($v)) {
        $result[$k] = $k;
      }
      else {
        $result[$k] = $v;
      }
    }
    asort($result);

    return $result;
  }

  /**
   * Callback for ajax requests.
   */
  public function ajaxCallback(array $form, array &$form_state) {
    return $form;
  }

  /**
   * Callback for target configuration forms.
   */
  public function configurationAjaxCallback(array $form, array &$form_state) {
    $delta = $form_state['triggering_element']['#delta'];
    return $form['mappings'][$delta]['configuration'];
  }

}
