<?php

/**
 * @file
 * Contains \Drupal\feeds\Form\MappingForm.
 *
 * @todo This needs some love.
 */

namespace Drupal\feeds\Form;

use Drupal\Component\Utility\String;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\ImporterInterface;
use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;

/**
 * Provides a form for mapping settings.
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
  public function buildForm(array $form, FormStateInterface $form_state, ImporterInterface $feeds_importer = NULL) {
    $importer = $this->importer = $feeds_importer;
    $this->targets = $targets = $importer->getMappingTargets();

    // Denormalize targets.
    $this->sourceOptions = array();
    foreach ($importer->getMappingSources() as $key => $info) {
      $this->sourceOptions[$key] = $info['label'];
    }
    $this->sourceOptions = $this->sortOptions($this->sourceOptions);

    $target_options = array();
    foreach ($targets as $key => $info) {
      $target_options[$key] = $info['label'];
    }
    $target_options = $this->sortOptions($target_options);

    if ($form_state->getValues()) {
      $this->processFormState($form, $form_state);

      if ($form_state->getTriggeringElement()['#name'] == 'add_target' || !empty($form_state->getTriggeringElement()['#remove'])) {
        drupal_set_message($this->t('Your changes will not be saved until you click the <em>Save</em> button at the bottom of the page.'), 'warning');
      }
    }

    $form['#tree'] = TRUE;
    $form['#prefix'] = '<div id="feeds-mapping-form-ajax-wrapper">';
    $form['#suffix'] = '</div>';
    $form['#attached']['css'][] = drupal_get_path('module', 'feeds') . '/feeds.css';

    $table = array(
      '#type' => 'table',
      '#header' => array(
        $this->t('Source'),
        $this->t('Target'),
        $this->t('Summary'),
        $this->t('Configure'),
        $this->t('Unique'),
        $this->t('Remove'),
      ),
      '#sticky' => TRUE,
    );

    foreach ($importer->getMappings() as $delta => $mapping) {
      $table[$delta] = $this->buildRow($form, $form_state, $mapping, $delta);
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

    $table['add']['summary']['#markup'] = '';
    $table['add']['configure']['#markup'] = '';
    $table['add']['unique']['#markup'] = '';
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
   *
   */
  protected function buildRow($form, $form_state, $mapping, $delta) {
    $ajax_delta = -1;
    if (isset($form_state->getTriggeringElement()['#delta']) && empty($form_state->getTriggeringElement()['#saved'])) {
      $ajax_delta = $form_state->getTriggeringElement()['#delta'];
    }

    $row = array(
      '#attributes' => array(
        'class' => array('draggable', 'tabledrag-leaf'),
      ),
    );
    $row['map'] = array(
      '#type' => 'container',
    );
    $row['targets'] = array(
      '#theme' => 'item_list',
      '#items' => array(),
    );

    foreach ($mapping['map'] as $column => $source) {
      if (!isset($this->targets[$mapping['target']]['properties'][$column])) {
        unset($mapping['map'][$column]);
        continue;
      }
      $row['map'][$column] = array(
        '#type' => 'select',
        '#options' => $this->sourceOptions,
        '#default_value' => $source,
        '#empty_option' => $this->t('- Select a source -'),
        '#attributes' => array('class' => array('feeds-table-select-list')),
      );

      $label = String::checkPlain($this->targets[$mapping['target']]['label']);

      if (count($mapping['map']) > 1) {
        $label .= ': ' . $this->targets[$mapping['target']]['properties'][$column]['label'];
      }
      $row['targets']['#items'][] = $label;
    }

    if ($plugin = $this->importer->getTargetPlugin($delta)) {

      $row['settings'] = array(
        '#type' => 'container',
        '#id' => 'edit-mappings-' . $delta . '-settings',
      );

      if ($plugin instanceof ConfigurableTargetInterface) {
        if ($delta == $ajax_delta) {
          $row['settings'] += $plugin->buildConfigurationForm(array(), $form_state);
          $row['settings']['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Save'),
            '#ajax' => array(
              'callback' => array($this, 'settingsAjaxCallback'),
              'wrapper' => 'edit-mappings-' . $delta . '-settings',
              'effect' => 'fade',
              'progress' => 'none',
            ),
            '#name' => 'target-save-' . $delta,
            '#delta' => $delta,
            '#saved' => TRUE,
            '#parents' => array('config_button', $delta),
            '#attributes' => array('class' => array('feeds-ajax-save-button')),
          );
        }
        else {
          $row['settings']['summary'] = array(
            '#type' => 'item',
            '#markup' => $plugin->getSummary(),
            '#parents' => array('config_summary', $delta),
            '#attributes' => array('class' => array('field-plugin-summary')),
          );
          $row['configure']['button'] = array(
            '#type' => 'image_button',
            '#op' => 'configure',
            '#ajax' => array(
              'callback' => array($this, 'settingsAjaxCallback'),
              'wrapper' => 'edit-mappings-' . $delta . '-settings',
              'effect' => 'fade',
              'progress' => 'none',
            ),
            '#name' => 'target-settings-' . $delta,
            '#delta' => $delta,
            '#parents' => array('config_button', $delta),
            '#attributes' => array('class' => array('feeds-ajax-configure-button')),
            '#src' => 'core/misc/configure-dark.png',
          );
        }
      }
      else {
        $row['configure']['#markup'] = '';
      }
    }
    else {
      $row['settings']['#markup'] = '';
      $row['configure']['#markup'] = '';
    }

    $mappings = $this->importer->getMappings();

    foreach ($mapping['map'] as $column => $source) {
      if (!empty($this->targets[$mapping['target']]['unique'][$column])) {
        $row['unique'][$column] = array(
          '#title' => $this->t('Unique'),
          '#type' => 'checkbox',
          '#default_value' => !empty($mappings[$delta]['unique'][$column]),
          '#title_display' => 'invisible',
        );
      }
      else {
        $row['unique']['#markup'] = '';
      }
    }

    $row['remove'] = array(
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

    return $row;
  }

  /**
   * Processes the form state, populating the mapping array.
   *
   * @param FormStateInterface $form_state
   *   The form state array to process.
   */
  protected function processFormState(array $form, FormStateInterface $form_state) {
    // Process any plugin configuration.
    if (isset($form_state->getTriggeringElement()['#delta']) && !empty($form_state->getTriggeringElement()['#saved'])) {
      $delta = $form_state->getTriggeringElement()['#delta'];
      $this->importer->getTargetPlugin($delta)->submitConfigurationForm($form, $form_state);
    }

    if ($form_state->getValue('mappings')) {
      foreach ($form_state->getValue('mappings') as $delta => $mapping) {
        $this->importer->setMapping($delta, $mapping);
      }
    }

    // Remove any mappings.
    if ($form_state->getValue('remove_mappings')) {
      foreach (array_keys(array_filter($form_state->getValue('remove_mappings'))) as $delta) {
        $this->importer->removeMapping($delta);
      }
    }

    // Add any targets.
    if ($new_target = $form_state->getValue('add_target')) {
      $map = array_fill_keys(array_keys($this->targets[$new_target]['properties']), '');
      $this->importer->addMapping(array(
        'target' => $form_state->getValue('add_target'),
        'map' => $map,
      ));
    }

    // Allow the #default_value of 'add_target' to be reset.
    // unset($form_state['input']['add_target']);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (isset($form_state->getTriggeringElement()['#delta'])) {
      $delta = $form_state->getTriggeringElement()['#delta'];
      $this->importer->getTargetPlugin($delta)->validateConfigurationForm($form, $form_state);
      $form_state->setRebuild();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->processFormState($form, $form_state);
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
  public function ajaxCallback(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Callback for target settings forms.
   */
  public function settingsAjaxCallback(array $form, FormStateInterface $form_state) {
    $delta = $form_state->getTriggeringElement()['#delta'];
    return $form['mappings'][$delta]['settings'];
  }

}
