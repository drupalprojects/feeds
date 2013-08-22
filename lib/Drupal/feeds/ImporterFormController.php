<?php

/**
 * @file
 * Definition of \Drupal\feeds\ImporterFormController.
 */

namespace Drupal\feeds;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityControllerInterface;
use Drupal\Core\Entity\EntityFormController;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\feeds\AdvancedFormPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the importer edit forms.
 */
class ImporterFormController extends EntityFormController implements EntityControllerInterface  {

  /**
   * The entity manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $entityManager;

  /**
   * The importer storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $importerStorage;

  /**
   * The plugin managers keyed by plugin type.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface[]
   */
  protected $managers = array();

  /**
   * The plugngs that provide configuration forms.
   *
   * @var array
   */
  protected $configurablePlugins = array();

  /**
   * Constructs a new action form.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $storage_controller
   *   The action storage controller.
   *
   * @todo
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    PluginManagerInterface $entity_manager,
    EntityStorageControllerInterface $importer_storage,
    PluginManagerInterface $fetcher_manager,
    PluginManagerInterface $parser_manager,
    PluginManagerInterface $processer_manager) {

    parent::__construct($module_handler);

    $this->entityManager = $entity_manager;
    $this->importerStorage = $importer_storage;
    $this->managers['fetcher'] = $fetcher_manager;
    $this->managers['parser'] = $parser_manager;
    $this->managers['processor'] = $processer_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, $entity_type, array $entity_info) {
    $entity_manager = $container->get('plugin.manager.entity');
    return new static(
      $container->get('module_handler'),
      $entity_manager,
      $entity_manager->getStorageController('feeds_importer'),
      $container->get('plugin.manager.feeds.fetcher'),
      $container->get('plugin.manager.feeds.parser'),
      $container->get('plugin.manager.feeds.processor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $form['#tree'] = TRUE;

    $form['basics'] = array(
      '#title' => t('Basic settings'),
      '#type' => 'details',
      '#tree' => FALSE,
      '#collapsed' => !$this->entity->isNew(),
    );

    $form['basics']['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#default_value' => $this->entity->label(),
      '#maxlength' => '255',
      '#description' => t('A unique label for this importer. This label will be displayed in the interface.'),
    );

    $form['basics']['id'] = array(
      '#type' => 'machine_name',
      '#title' => t('Machine name'),
      '#default_value' => $this->entity->id(),
      '#disabled' => !$this->entity->isNew(),
      '#maxlength' => 64,
      '#description' => t('A unique name for this importer. It must only contain lowercase letters, numbers and underscores.'),
      '#machine_name' => array(
        'exists' => array($this, 'exists'),
      ),
    );
    $form['basics']['description'] = array(
      '#type' => 'textfield',
      '#title' => t('Description'),
      '#description' => t('A description of this importer.'),
      '#default_value' => $this->entity->description,
    );
    $cron_required =  ' ' . l(t('Requires cron to be configured.'), 'http://drupal.org/cron', array('attributes' => array('target' => '_new')));
    $period = drupal_map_assoc(array(900, 1800, 3600, 10800, 21600, 43200, 86400, 259200, 604800, 2419200), 'format_interval');
    foreach ($period as &$p) {
      $p = t('Every !p', array('!p' => $p));
    }
    $period = array(
      FEEDS_SCHEDULE_NEVER => t('Off'),
      0 => t('As often as possible'),
    ) + $period;

    $form['import_period'] = array(
      '#type' => 'select',
      '#title' => t('Periodic import'),
      '#options' => $period,
      '#description' => t('Choose how often a source should be imported periodically.') . $cron_required,
      '#default_value' => $this->entity->import_period,
    );
    $form['import_on_create'] = array(
      '#type' => 'checkbox',
      '#title' => t('Import on submission'),
      '#description' => t('Check if import should be started at the moment a standalone form or node form is submitted.'),
      '#default_value' => $this->entity->import_on_create,
    );
    $form['process_in_background'] = array(
      '#type' => 'checkbox',
      '#title' => t('Process in background'),
      '#description' => t('For very large imports. If checked, import and delete tasks started from the web UI will be handled by a cron task in the background rather than by the browser. This does not affect periodic imports, they are handled by a cron task in any case.') . $cron_required,
      '#default_value' => $this->entity->process_in_background,
    );

    $form['plugin_settings'] = array(
      '#type' => 'vertical_tabs',
      '#weight' => 99,
    );

    $form['plugin_settings']['#prefix'] = '<div id="feeds-ajax-form-wrapper">';
    $form['plugin_settings']['#suffix'] = '</div>';

    if (isset($form_state['values'])) {
      foreach ($this->entity->getPluginTypes() as $type) {
        $this->entity->setPlugin($type, $form_state['values'][$type]['plugin_key']);
      }
    }

    foreach ($this->entity->getPlugins() as $type => $plugin) {
      $definitions = $this->managers[$type]->getDefinitions();

      $options = array();
      foreach ($definitions as $key => $definition) {
        $options[$key] = check_plain($definition['title']);
      }

      $form[$type]['plugin_key'] = array(
        '#type' => 'select',
        '#title' => t('@type', array('@type' => ucfirst($type))),
        '#options' => $options,
        '#default_value' => $plugin->getPluginID(),
        '#ajax' => array(
          'callback' => array($this, 'ajaxCallback'),
          'wrapper' => 'feeds-ajax-form-wrapper',
          'effect' => 'fade',
          'progress' => 'none',
        ),
        '#plugin_type' => $type,
      );

      if ($plugin instanceof AdvancedFormPluginInterface) {

        $this->configurablePlugins[$type] = $plugin;
        $form[$type]['advanced'] = $plugin->buildAdvancedForm(array(), $form_state);
      }

      $form[$type]['advanced']['#prefix'] = '<div id="feeds-plugin-' . $type . '-advanced">';
      $form[$type]['advanced']['#suffix'] = '</div>';

      if ($plugin instanceof FormInterface) {

        $this->configurablePlugins[$type] = $plugin;

        $form[$type . '_config'] = array(
          '#type' => 'details',
          '#group' => 'plugin_settings',
          '#title' => t('@type settings', array('@type' => ucfirst($type))),
          '#parents' => array($type, 'config'),
        );

        $form[$type . '_config'] += $plugin->buildForm(array(), $form_state);
      }
    }

    return parent::form($form, $form_state);
  }

  /**
   * Determines if the importer already exists.
   *
   * @param string $id
   *   The importer ID
   *
   * @return bool
   *   TRUE if the importer exists, FALSE otherwise.
   */
  public function exists($id) {
    $importer = $this->importerStorage->load($id);
    return !empty($importer);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, array &$form_state) {
    $actions = parent::actions($form, $form_state);
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, array &$form_state) {
    foreach ($this->entity->getPluginTypes() as $type) {
      if (isset($form_state['values'][$type]['advanced'])) {
        $form_state['values'][$type]['config'] += $form_state['values'][$type]['advanced'];
        unset($form_state['values'][$type]['advanced']);
      }
    }

    foreach ($this->configurablePlugins as $plugin) {
      $plugin->validateForm($form, $form_state);
    }

    // Build the importer object from the submitted values.
    return parent::validate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {

    foreach ($this->configurablePlugins as $plugin) {
      $plugin->submitForm($form, $form_state);
    }

    if ($this->entity->import_period != $form_state['values']['import_period']) {
      $this->entity->reschedule($this->entity->id());
    }

    // Build the importer object from the submitted values.
    return parent::submit($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    $importer = $this->entity;
    unset($importer->plugin_settings);
    unset($importer->actions);
    $importer->save();
    drupal_set_message(t('Your changes have been saved.'));
  }

  public function ajaxCallback(array $form, array &$form_state) {
    $type = $form_state['triggering_element']['#plugin_type'];

    $response = new AjaxResponse();
    $status_messages = array('#theme' => 'status_messages');
    // $response->addCommand(new ReplaceCommand(NULL, drupal_render($status_messages)));
    $response->addCommand(new ReplaceCommand('#feeds-ajax-form-wrapper', drupal_render($form['plugin_settings'])));
    $response->addCommand(new ReplaceCommand('#feeds-plugin-' . $type . '-advanced', drupal_render($form[$type]['advanced'])));

    return $response;
  }

}
