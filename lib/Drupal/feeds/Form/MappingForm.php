<?php

/**
 * @file
 * Contains \Drupal\feeds\Form\MappingForm.
 *
 * @todo This needs some love.
 */

namespace Drupal\feeds\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\feeds\ImporterInterface;

/**
 * Provides a form for mapping configuration.
 */
class MappingForm implements FormInterface {

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

    $target_options = array();
    foreach ($targets as $key => $info) {
      $target_options[$key] = $info['label'];
    }
    if (isset($form_state['values'])) {
      dpm($form_state);
      $new_target = $form_state['values']['add_target'];
      if ($new_target) {
        $map = array_fill_keys(array_keys($this->targets[$new_target]['properties']), '');
        $this->mappings[] = array('target' => $form_state['values']['add_target'], 'map' => $map);
      }
      unset($form_state['values']['add_target']);
      foreach (array_keys(array_filter($form_state['values']['remove_mappings'])) as $delta) {
        unset($this->mappings[$delta]);
      }
    }

    $form['#tree'] = TRUE;
    $form['#prefix'] = '<div id="feeds-mapping-form-ajax-wrapper">';
    $form['#suffix'] = '</div>';

    $table = array(
      '#type' => 'table',
      '#header' => array(t('Source'), t('Target'), t('Remove')),
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
          '#empty_option' => t('- Select a source -'),
        );

        $label = check_plain($targets[$mapping['target']]['label']);

        if (count($mapping['map']) > 1) {
          $label .= ': ' . $targets[$mapping['target']]['properties'][$column]['label'];
        }

        $table[$delta]['targets']['#items'][] =  $label;
      }

      $table[$delta]['remove'] = array(
        '#title' => t('Remove'),
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
      );
    }

    $table['add']['source']['#markup'] = '';

    $table['add']['target'] = array(
      '#type' => 'select',
      '#title' => t('Add a target'),
      '#title_display' => 'invisible',
      '#options' => $target_options,
      '#empty_option' => t('- Select a target -'),
      '#parents' => array('add_target'),
      '#ajax' => array(
        'callback' => array($this, 'ajaxCallback'),
        'wrapper' => 'feeds-mapping-form-ajax-wrapper',
        'effect' => 'none',
        'progress' => 'none',
      ),
    );
    $table['add']['remove']['#markup'] = '';

    $form['mappings'] = $table;

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Save'));

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->mappings = $form_state['values']['mappings'];

    // Add new target.
    $new_target = $form_state['values']['add_target'];
    if ($new_target) {
      $map = array_fill_keys(array_keys($this->targets[$new_target]['properties']), '');
      $this->mappings[] = array('target' => $form_state['values']['add_target'], 'map' => $map);
    }

    // Remove existing mapping.
    foreach (array_keys(array_filter($form_state['values']['remove_mappings'])) as $delta) {
      unset($this->mappings[$delta]);
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

  public function ajaxCallback(array $form, array &$form_state) {
    return $form;
  }

}
