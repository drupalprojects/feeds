<?php

/**
 * @file
 * Definition of \Drupal\feeds\ImporterFormController.
 */

namespace Drupal\feeds;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\feeds\Ajax\SetHashCommand;
use Drupal\feeds\Plugin\Type\AdvancedFormPluginInterface;
use Drupal\feeds\Plugin\Type\LockableInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the importer edit forms.
 */
class ImporterFormController extends EntityForm {

  /**
   * The importer storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $importerStorage;

  /**
   * Constructs an ImporterFormController object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $importer_storage
   *   The importer storage controller.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->importerStorage = $entity_manager->getStorage('feeds_importer');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $form['#tree'] = TRUE;
    $form['#attached']['css'][] = drupal_get_path('module', 'feeds') . '/feeds.css';

    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('Edit %label', array('%label' => $this->entity->label()));
    }

    $form['basics'] = array(
      '#title' => $this->t('Basic settings'),
      '#type' => 'details',
      '#tree' => FALSE,
      '#open' => $this->entity->isNew(),
    );

    $form['basics']['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $this->entity->label(),
      '#maxlength' => '255',
      '#description' => $this->t('A unique label for this importer. This label will be displayed in the interface.'),
      '#required' => TRUE,
    );

    $form['basics']['id'] = array(
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $this->entity->id(),
      '#disabled' => !$this->entity->isNew(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#description' => $this->t('A unique name for this importer. It must only contain lowercase letters, numbers and underscores.'),
      '#machine_name' => array(
        'exists' => 'node_type_load',
        'source' => array('basics', 'label'),
      ),
      '#required' => TRUE,
    );
    $form['basics']['description'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#description' => $this->t('A description of this importer.'),
      '#default_value' => $this->entity->description,
    );

    $form['plugin_settings'] = array(
      '#type' => 'vertical_tabs',
      '#weight' => 99,
    );

    $form['plugin_settings']['#prefix'] = '<div id="feeds-ajax-form-wrapper" class="theme-settings-bottom">';
    $form['plugin_settings']['#suffix'] = '</div>';

    // If this is an ajax requst, updating the plugins on the importer will give
    // us the updated form.
    if (!empty($values)) {
      if ($values['processor']['id'] != $this->entity->getProcessor()->getPluginId()) {
        $this->entity->removeMappings();
      }
      foreach ($this->entity->getPluginTypes() as $type) {
        $this->entity->setPlugin($type, $values[$type]['id']);
      }
    }

    foreach ($this->entity->getPlugins() as $type => $plugin) {
      $options = $this->entity->getPluginOptionsList($type);

      $form[$type] = array(
        '#type' => 'container',
        '#attributes' => array('class' => array('feeds-plugin-inline')),
      );

      if (count($options) === 1) {
        $form[$type]['id'] = array(
          '#type' => 'value',
          '#value' => $plugin->getPluginId(),
          '#plugin_type' => $type,
        );
      }
      else {
        $form[$type]['id'] = array(
          '#type' => 'select',
          '#title' => $this->t('@type', array('@type' => ucfirst($type))),
          '#options' => $options,
          '#default_value' => $plugin->getPluginId(),
          '#ajax' => array(
            'callback' => '::ajaxCallback',
            'wrapper' => 'feeds-ajax-form-wrapper',
            // 'effect' => 'none',
            'progress' => 'none',
          ),
          '#attached' => array(
            'library' => array('feeds/feeds'),
          ),
          '#plugin_type' => $type,
        );
      }

      // Give lockable plugins a chance to lock themselves.
      // @see \Drupal\feeds\Feeds\Processor\EntityProcessor::isLocked()
      if ($plugin instanceof LockableInterface) {
        $form[$type]['id']['#disabled'] = $plugin->isLocked();
      }

      // This is the small form that appears under the select box.
      if ($plugin instanceof AdvancedFormPluginInterface) {
        $form[$type]['advanced'] = $plugin->buildAdvancedForm(array(), $form_state);
      }

      $form[$type]['advanced']['#prefix'] = '<div id="feeds-plugin-' . $type . '-advanced">';
      $form[$type]['advanced']['#suffix'] = '</div>';

      if ($plugin instanceof PluginFormInterface) {
        if ($plugin_form = $plugin->buildConfigurationForm(array(), $form_state)) {
          $form[$type . '_configuration'] = array(
            '#type' => 'details',
            '#group' => 'plugin_settings',
            '#title' => $this->t('@type settings', array('@type' => ucfirst($type))),
            '#parents' => array($type, 'configuration'),
          );
          // $form[$type . '_configuration']['#prefix'] = '<span id="feeds-' . $type . '-details">';
          // $form[$type . '_configuration']['#suffix'] = '</span>';

          $form[$type . '_configuration'] += $plugin_form;
        }
      }
    }

    return parent::form($form, $form_state);
  }

  /**
   * Determines if the importer already exists.
   *
   * @param string $id
   *   The importer ID.
   *
   * @return bool
   *   True if the importer exists, false otherwise.
   */
  public function exists($id) {
    return (bool) $this->importerStorage->load($id);
  }

  /**
   * Returns the configurable plugins for this importer.
   *
   * @return array
   *   A plugin array keyed by plugin type.
   *
   * @todo Consider moving this to Importer.
   */
  protected function getConfigurablePlugins() {
    $plugins = array();

    foreach ($this->entity->getPlugins() as $type => $plugin) {
      if ($plugin instanceof PluginFormInterface || $plugin instanceof AdvancedFormPluginInterface) {
        $plugins[$type] = $plugin;
      }
    }

    return $plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
    $values =& $form_state->getValues();

    // Moved advanced settings to regular settings.
    foreach ($this->entity->getPluginTypes() as $type) {
      if (isset($values[$type]['advanced'])) {
        if (!isset($values[$type]['configuration'])) {
          $values[$type]['configuration'] = array();
        }
        $values[$type]['configuration'] += $values[$type]['advanced'];
        unset($values[$type]['advanced']);
      }
    }

    foreach ($this->getConfigurablePlugins() as $plugin) {
      $plugin->validateConfigurationForm($form, $form_state);
    }

    // Build the importer object from the submitted values.
    parent::validate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    foreach ($this->getConfigurablePlugins() as $plugin) {
      $plugin->submitConfigurationForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    if ($this->entity->isNew()) {
      // $form_state['redirect'] = 'admin/structure/feeds/manage/' . $this->entity->id();
    }

    $this->entity->save();
    // $form_state['redirect'] = 'admin/structure/feeds/manage/' . $this->entity->id() . '/mapping';
    drupal_set_message($this->t('Your changes have been saved.'));
  }

  /**
   * Sends an ajax response.
   */
  public function ajaxCallback(array $form, FormStateInterface $form_state) {
    $type = $form_state->getTriggeringElement()['#plugin_type'];
    $response = new AjaxResponse();

    if (isset($form[$type . '_configuration']['#id'])) {
      $hash = ltrim($form[$type . '_configuration']['#id'], '#');
      $response->addCommand(new SetHashCommand($hash));
    }
    $response->addCommand(new ReplaceCommand('#feeds-ajax-form-wrapper', drupal_render($form['plugin_settings'])));
    $response->addCommand(new ReplaceCommand('#feeds-plugin-' . $type . '-advanced', drupal_render($form[$type]['advanced'])));

    return $response;
  }

}
