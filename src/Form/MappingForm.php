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
    $this->sourceOptions = [];
    foreach ($importer->getMappingSources() as $key => $info) {
      $this->sourceOptions[$key] = $info['label'];
    }
    $this->sourceOptions = $this->sortOptions($this->sourceOptions);

    $target_options = [];
    foreach ($targets as $key => $target) {
      $target_options[$key] = $target->getLabel();
    }
    $target_options = $this->sortOptions($target_options);

    if ($form_state->getValues()) {
      $this->processFormState($form, $form_state);

      if ($form_state->getTriggeringElement()['#name'] === 'add_target' || !empty($form_state->getTriggeringElement()['#remove'])) {
        drupal_set_message($this->t('Your changes will not be saved until you click the <em>Save</em> button at the bottom of the page.'), 'warning');
      }
    }

    $form['#tree'] = TRUE;
    $form['#prefix'] = '<div id="feeds-mapping-form-ajax-wrapper">';
    $form['#suffix'] = '</div>';
    $form['#attached']['css'][] = drupal_get_path('module', 'feeds') . '/feeds.css';

    $table = [
      '#type' => 'table',
      '#header' => [
        $this->t('Source'),
        $this->t('Target'),
        $this->t('Summary'),
        $this->t('Configure'),
        $this->t('Unique'),
        $this->t('Remove'),
      ],
      '#sticky' => TRUE,
    ];

    foreach ($importer->getMappings() as $delta => $mapping) {
      $table[$delta] = $this->buildRow($form, $form_state, $mapping, $delta);
    }

    $table['add']['source']['#markup'] = '';

    $table['add']['target'] = [
      '#type' => 'select',
      '#title' => $this->t('Add a target'),
      '#title_display' => 'invisible',
      '#options' => $target_options,
      '#empty_option' => $this->t('- Select a target -'),
      '#parents' => ['add_target'],
      '#default_value' => NULL,
      '#ajax' => [
        'callback' => get_class($this) . '::ajaxCallback',
        'wrapper' => 'feeds-mapping-form-ajax-wrapper',
        'effect' => 'none',
        'progress' => 'none',
      ],
    ];

    $table['add']['summary']['#markup'] = '';
    $table['add']['configure']['#markup'] = '';
    $table['add']['unique']['#markup'] = '';
    $table['add']['remove']['#markup'] = '';

    $form['mappings'] = $table;

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

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

    $row = ['#attributes' => ['class' => ['draggable', 'tabledrag-leaf']]];
    $row['map'] = ['#type' => 'container'];
    $row['targets'] = [
      '#theme' => 'item_list',
      '#items' => [],
    ];

    foreach ($mapping['map'] as $column => $source) {
      if (!$this->targets[$mapping['target']]->hasProperty($column)) {
        unset($mapping['map'][$column]);
        continue;
      }
      $row['map'][$column] = [
        '#type' => 'select',
        '#options' => $this->sourceOptions,
        '#default_value' => $source,
        '#empty_option' => $this->t('- Select a source -'),
        '#attributes' => ['class' => ['feeds-table-select-list']],
      ];

      $label = String::checkPlain($this->targets[$mapping['target']]->getLabel());

      if (count($mapping['map']) > 1) {
        $label .= ': ' . $this->targets[$mapping['target']]->getPropertyLabel($column);
      }
      else {
        $label .= ': ' . $this->targets[$mapping['target']]->getDescription();
      }
      $row['targets']['#items'][] = $label;
    }

    if ($plugin = $this->importer->getTargetPlugin($delta)) {

      $row['settings'] = [
        '#type' => 'container',
        '#id' => 'edit-mappings-' . $delta . '-settings',
      ];

      if ($plugin instanceof ConfigurableTargetInterface) {
        if ($delta == $ajax_delta) {
          $row['settings'] += $plugin->buildConfigurationForm([], $form_state);
          $row['settings']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save'),
            '#ajax' => [
              'callback' => get_class($this) . '::settingsAjaxCallback',
              'wrapper' => 'edit-mappings-' . $delta . '-settings',
              'effect' => 'fade',
              'progress' => 'none',
            ],
            '#name' => 'target-save-' . $delta,
            '#delta' => $delta,
            '#saved' => TRUE,
            '#parents' => ['config_button', $delta],
            '#attributes' => ['class' => ['feeds-ajax-save-button']],
          ];
        }
        else {
          $row['settings']['summary'] = [
            '#type' => 'item',
            '#markup' => $plugin->getSummary(),
            '#parents' => ['config_summary', $delta],
            '#attributes' => ['class' => ['field-plugin-summary']],
          ];
          $row['configure']['button'] = [
            '#type' => 'image_button',
            '#op' => 'configure',
            '#ajax' => [
              'callback' => get_class($this) . '::settingsAjaxCallback',
              'wrapper' => 'edit-mappings-' . $delta . '-settings',
              'effect' => 'fade',
              'progress' => 'none',
            ],
            '#name' => 'target-settings-' . $delta,
            '#delta' => $delta,
            '#parents' => ['config_button', $delta],
            '#attributes' => ['class' => ['feeds-ajax-configure-button']],
            '#src' => 'core/misc/icons/787878/cog.svg',
          ];
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
      if ($this->targets[$mapping['target']]->isUnique($column)) {
        $row['unique'][$column] = [
          '#title' => $this->t('Unique'),
          '#type' => 'checkbox',
          '#default_value' => !empty($mappings[$delta]['unique'][$column]),
          '#title_display' => 'invisible',
        ];
      }
      else {
        $row['unique']['#markup'] = '';
      }
    }

    $row['remove'] = [
      '#title' => $this->t('Remove'),
      '#type' => 'checkbox',
      '#default_value' => FALSE,
      '#title_display' => 'invisible',
      '#parents' => ['remove_mappings', $delta],
      '#ajax' => [
        'callback' => get_class($this) . '::ajaxCallback',
        'wrapper' => 'feeds-mapping-form-ajax-wrapper',
        'effect' => 'none',
        'progress' => 'none',
      ],
      '#remove' => TRUE,
    ];

    return $row;
  }

  /**
   * Processes the form state, populating the mappings on the importer.
   */
  protected function processFormState(array $form, FormStateInterface $form_state) {
    // Process any plugin configuration.
    if (isset($form_state->getTriggeringElement()['#delta']) && !empty($form_state->getTriggeringElement()['#saved'])) {
      $delta = $form_state->getTriggeringElement()['#delta'];
      $this->importer->getTargetPlugin($delta)->submitConfigurationForm($form, $form_state);
    }

    $mappings = $this->importer->getMappings();
    foreach (array_filter((array) $form_state->getValue('mappings', [])) as $delta => $mapping) {
      $mappings[$delta]['map'] = $mapping['map'];
      if (isset($mapping['unique'])) {
        $mappings[$delta]['unique'] = array_filter($mapping['unique']);
      }
    }
    $this->importer->setMappings($mappings);

    // Remove any mappings.
    foreach (array_keys(array_filter($form_state->getValue('remove_mappings', []))) as $delta) {
      $this->importer->removeMapping($delta);
    }

    // Add any targets.
    if ($new_target = $form_state->getValue('add_target')) {
      $map = array_fill_keys($this->targets[$new_target]->getProperties(), '');
      $this->importer->addMapping([
        'target' => $new_target,
        'map' => $map,
      ]);
    }

    // Allow the #default_value of 'add_target' to be reset.
    $input =& $form_state->getUserInput();
    unset($input['add_target']);
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
    $result = [];
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
  public static function ajaxCallback(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Callback for target settings forms.
   */
  public static function settingsAjaxCallback(array $form, FormStateInterface $form_state) {
    $delta = $form_state->getTriggeringElement()['#delta'];
    return $form['mappings'][$delta]['settings'];
  }

}
