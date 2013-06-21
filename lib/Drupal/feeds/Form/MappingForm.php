<?php

/**
 * @file
 * Contains \Drupal\feeds\Form\MappingForm.
 */

namespace Drupal\feeds\Form;

use Drupal\Core\Form\BaseFormIdInterface;
use Drupal\feeds\ImporterInterface;
use Drupal\feeds\Form\MappingSettingsForm;

/**
 * Provides a form for mapping.
 */
class MappingForm implements BaseFormIdInterface {

  /**
   * The feeds importer.
   *
   * @var \Drupal\feeds\ImporterInterface
   */
  protected $importer;

  /**
   * Constructs a new MappingForm object.
   *
   * @param \Drupal\feeds\ImporterInterface $importer
   */
  public function __construct(ImporterInterface $importer) {
    $this->importer = $importer;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormID() {
    return 'feeds_mapping_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'feeds_' . $this->importer->id() . '_mapping_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $importer = $this->importer;
    $form['#importer'] = $importer->id();
    $form['#mappings'] = $mappings = $importer->processor->getMappings();
    $form['help']['#markup'] = $this->help();
    $form['#prefix'] = '<div id="feeds-ui-mapping-form-wrapper">';
    $form['#suffix'] = '</div>';

    // Get mapping sources from parsers and targets from processor, format them
    // for output.
    // Some parsers do not define mapping sources but let them define on the fly.
    if ($sources = $importer->parser->getMappingSources()) {
      $source_options = $this->sortOptions($sources);
      foreach ($sources as $k => $source) {
        $legend['sources'][$k]['name']['#markup'] = empty($source['name']) ? $k : $source['name'];
        $legend['sources'][$k]['description']['#markup'] = empty($source['description']) ? '' : $source['description'];
      }
    }
    else {
      $legend['sources']['#markup'] = t('This parser supports free source definitions. Enter the name of the source field in lower case into the Source text field above.');
    }
    $targets = $importer->processor->getMappingTargets();
    $target_options = $this->sortOptions($targets);
    $legend['targets'] = array();
    foreach ($targets as $k => $target) {
      $legend['targets'][$k]['name']['#markup'] = empty($target['name']) ? $k : $target['name'];
      $legend['targets'][$k]['description']['#markup'] = empty($target['description']) ? '' : $target['description'];
    }

    // Legend explaining source and target elements.
    $form['legendset'] = array(
      '#type' => 'fieldset',
      '#title' => t('Legend'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#tree' => TRUE,
    );
    $form['legendset']['legend'] = $legend;

    // Add config forms and remove flags to mappings.
    $form['config'] = $form['remove_flags'] = $form['mapping_weight'] = array(
      '#tree' => TRUE,
    );
    if (is_array($mappings)) {

      $delta = count($mappings) + 2;

      foreach ($mappings as $i => $mapping) {
        if (isset($targets[$mapping['target']])) {
          $settings_form = new MappingSettingsForm($i, $mapping, $targets[$mapping['target']]);
          $form['config'][$i] = $settings_form->buildForm($form, $form_state);
        }

        $form['remove_flags'][$i] = array(
          '#type' => 'checkbox',
          '#title' => t('Remove'),
          '#prefix' => '<div class="feeds-ui-checkbox-link">',
          '#suffix' => '</div>',
        );

        $form['mapping_weight'][$i] = array(
          '#type' => 'weight',
          '#title' => '',
          '#default_value' => $i,
          '#delta' => $delta,
          '#attributes' => array(
            'class' => array(
              'feeds-ui-mapping-weight'
            ),
          ),
        );
      }
    }

    if (isset($source_options)) {
      $form['source'] = array(
        '#type' => 'select',
        '#title' => t('Source'),
        '#title_display' => 'invisible',
        '#options' => $source_options,
        '#empty_option' => t('- Select a source -'),
        '#description' => t('An element from the feed.'),
      );
    }
    else {
      $form['source'] = array(
        '#type' => 'textfield',
        '#title' => t('Source'),
        '#title_display' => 'invisible',
        '#size' => 20,
        '#default_value' => '',
        '#description' => t('The name of source field.'),
      );
    }
    $form['target'] = array(
      '#type' => 'select',
      '#title' => t('Target'),
      '#title_display' => 'invisible',
      '#options' => $target_options,
      '#empty_option' => t('- Select a target -'),
      '#description' => t('The field that stores the data.'),
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['save'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    if (empty($form_state['values']['source']) xor empty($form_state['values']['target'])) {

      // Check triggering_element here so we can react differently for ajax
      // submissions.
      switch ($form_state['triggering_element']['#name']) {

        // Regular form submission.
        case 'op':
          if (empty($form_state['values']['source'])) {
            form_error($form['source'], t('You must select a mapping source.'));
          }
          else {
            form_error($form['target'], t('You must select a mapping target.'));
          }
          break;

        // Be more relaxed on ajax submission.
        default:
          form_set_value($form['source'], '', $form_state);
          form_set_value($form['target'], '', $form_state);
          break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $importer = feeds_importer($form['#importer']);
    $processor = $importer->processor;

    $form_state += array(
      'mapping_settings' => array(),
      'mapping_settings_edit' => NULL,
    );

    // If an item is in edit mode, prepare it for saving.
    if ($form_state['mapping_settings_edit'] !== NULL) {
      $values = $form_state['values']['config'][$form_state['mapping_settings_edit']]['settings'];
      $form_state['mapping_settings'][$form_state['mapping_settings_edit']] = $values;
    }

    // We may set some settings to mappings that we remove in the subsequent step,
    // that's fine.
    $mappings = $form['#mappings'];
    foreach ($form_state['mapping_settings'] as $k => $v) {
      $mappings[$k] = array(
        'source' => $mappings[$k]['source'],
        'target' => $mappings[$k]['target'],
      ) + $v;
    }

    if (!empty($form_state['values']['remove_flags'])) {
      $remove_flags = array_keys(array_filter($form_state['values']['remove_flags']));

      foreach ($remove_flags as $k) {
        unset($mappings[$k]);
        unset($form_state['values']['mapping_weight'][$k]);
        drupal_set_message(t('Mapping has been removed.'), 'status', FALSE);
      }
    }

    // Keep our keys clean.
    $mappings = array_values($mappings);

    if (!empty($mappings)) {
      array_multisort($form_state['values']['mapping_weight'], $mappings);
    }

    $processor->addConfig(array('mappings' => $mappings));

    if (!empty($form_state['values']['source']) && !empty($form_state['values']['target'])) {
      try {
        $mappings = $processor->getMappings();
        $mappings[] = array(
          'source' => $form_state['values']['source'],
          'target' => $form_state['values']['target'],
          'unique' => FALSE,
        );
        $processor->addConfig(array('mappings' => $mappings));
        drupal_set_message(t('Mapping has been added.'));
      }
      catch (Exception $e) {
        drupal_set_message($e->getMessage(), 'error');
      }
    }

    $importer->save();
    drupal_set_message(t('Your changes have been saved.'));
  }

  protected function sortOptions(array $options) {
    $result = array();
    foreach ($options as $k => $v) {
      if (is_array($v) && !empty($v['name'])) {
        $result[$k] = $v['name'];
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
   * Help text for mapping.
   */
  protected function help() {
    return t('
    <p>
    Define which elements of a single item of a feed (= <em>Sources</em>) map to which content pieces in Drupal (= <em>Targets</em>). Make sure that at least one definition has a <em>Unique target</em>. A unique target means that a value for a target can only occur once. E. g. only one item with the URL <em>http://example.com/content/1</em> can exist.
    </p>
    ');
  }

}
