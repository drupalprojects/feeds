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
    $this->mappings = $importer->getMappings();

    $sources = $importer->getParser()->getMappingSources();
    $targets = $importer->getProcessor()->getMappingTargets();

    $source_options = array();
    foreach ($sources as $key => $info) {
      $source_options[$key] = $info['label'];
    }

    $target_options = array();
    foreach ($targets as $key => $info) {
      $target_options[$key] = $info['label'];
    }

    $form['#tree'] = TRUE;

    $table = array(
      '#type' => 'table',
      '#header' => array(
        t('Source'),
        t('Target'),
        t('Remove'),
      ),
    );

    $rows = array();
    foreach ($this->mappings as $delta => $mapping) {
      $table[$delta]['source'] = array(
        '#type' => 'select',
        '#options' => $source_options,
        '#default_value' => $mapping['source'],
        '#empty_option' => t('- Select a source -'),
      );
      $table[$delta]['target_display'] = array(
        '#markup' => check_plain($targets[$mapping['target']]['label']),
      );
      $table[$delta]['remove'] = array(
        '#title' => t('Remove'),
        '#type' => 'checkbox',
        '#default_value' => FALSE,
        '#title_display' => 'invisible',
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
    // Add new target.
    if ($form_state['values']['add_target']) {
      $this->mappings[] = array('source' => NULL, 'target' => $form_state['values']['add_target']);
    }

    // Set sources for existing mappings.
    foreach ($form_state['values']['mappings'] as $delta => $mapping) {
      $this->mappings[$delta]['source'] = $mapping['source'];
    }

    // Remove existing mapping.
    foreach ($form_state['values']['mappings'] as $delta => $mapping) {
      if ($mapping['remove']) {
        unset($this->mappings[$delta]);
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

}
