<?php

/**
 * @file
 * Contains \Drupal\feeds\ImporterForm.
 */

namespace Drupal\feeds;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\feeds\Ajax\SetHashCommand;
use Drupal\feeds\Plugin\Type\AdvancedFormPluginInterface;
use Drupal\feeds\Plugin\Type\LockableInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the importer edit forms.
 */
class ImporterForm extends EntityForm {

  /**
   * The importer storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $importerStorage;

  /**
   * Constructs an ImporterForm object.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $importer_storage
   *   The importer storage controller.
   */
  public function __construct(ConfigEntityStorageInterface $importer_storage) {
    $this->importerStorage = $importer_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('feeds_importer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;
    $values = $form_state->getValues();

    $form['#attached']['css'][] = drupal_get_path('module', 'feeds') . '/feeds.css';

    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('Edit %label', array('%label' => $this->entity->label()));
    }

    $form['basics'] = array(
      '#title' => $this->t('Basic settings'),
      '#type' => 'details',
      '#open' => $this->entity->isNew(),
      '#tree' => FALSE,
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
        'exists' => 'Drupal\feeds\Entity\Importer::load',
        'source' => array('basics', 'label'),
      ),
      '#required' => TRUE,
    );
    $form['basics']['description'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#description' => $this->t('A description of this importer.'),
      '#default_value' => $this->entity->getDescription(),
    );

    $form['plugin_settings'] = array(
      '#type' => 'vertical_tabs',
      '#weight' => 99,
    );

    $form['plugin_settings']['#prefix'] = '<div id="feeds-ajax-form-wrapper" class="theme-settings-bottom">';
    $form['plugin_settings']['#suffix'] = '</div>';

    $form['importer_settings'] = array(
      '#type' => 'details',
      '#group' => 'plugin_settings',
      '#title' => $this->t('Settings'),
      '#tree' => FALSE,
    );
    $times = [900, 1800, 3600, 10800, 21600, 43200, 86400, 259200, 604800, 2419200];
    $period = array_map(function($time) {
      return \Drupal::service('date.formatter')->formatInterval($time);
    }, array_combine($times, $times));

    foreach ($period as &$p) {
      $p = $this->t('Every !p', array('!p' => $p));
    }
    $period = array(
      ImporterInterface::SCHEDULE_NEVER => $this->t('Off'),
      0 => $this->t('As often as possible'),
    ) + $period;

    $form['importer_settings']['import_period'] = array(
      '#type' => 'select',
      '#title' => $this->t('Import period'),
      '#options' => $period,
      '#description' => $this->t('Choose how often a feed should be imported.'),
      '#default_value' => $this->entity->getImportPeriod(),
    );

    // If this is an ajax requst, updating the plugins on the importer will give
    // us the updated form.
    if (!empty($values)) {
      if ($values['processor'] !== $this->entity->getProcessor()->getPluginId()) {
        $this->entity->removeMappings();
      }
      foreach (array_keys($this->entity->getPlugins()) as $type) {
        $this->entity->setPlugin($type, $values[$type]);
      }
    }

    foreach ($this->entity->getPlugins() as $type => $plugin) {
      $options = $this->entity->getPluginOptionsList($type);

      $form[$type . '_wrapper'] = array(
        '#type' => 'container',
        '#attributes' => array('class' => array('feeds-plugin-inline')),
      );

      if (count($options) === 1) {
        $form[$type . '_wrapper']['id'] = array(
          '#type' => 'value',
          '#value' => $plugin->getPluginId(),
          '#plugin_type' => $type,
          '#parents' => [$type],
        );
      }
      else {
        $form[$type . '_wrapper']['id'] = array(
          '#type' => 'select',
          '#title' => $this->t('@type', array('@type' => ucfirst($type))),
          '#options' => $options,
          '#default_value' => $plugin->getPluginId(),
          '#ajax' => array(
            'callback' => get_class($this) . '::ajaxCallback',
            'wrapper' => 'feeds-ajax-form-wrapper',
            'progress' => 'none',
          ),
          '#attached' => array(
            'library' => array('feeds/feeds'),
          ),
          '#plugin_type' => $type,
          '#parents' => [$type],
        );
      }

      // Give lockable plugins a chance to lock themselves.
      // @see \Drupal\feeds\Feeds\Processor\EntityProcessor::isLocked()
      if ($plugin instanceof LockableInterface) {
        $form[$type . '_wrapper']['id']['#disabled'] = $plugin->isLocked();
      }

      $plugin_state = (new FormState())->setValues($form_state->getValue([$type . '_configuration'], []));

      // This is the small form that appears under the select box.
      if ($plugin instanceof AdvancedFormPluginInterface) {
        $form[$type . '_wrapper']['advanced'] = $plugin->buildAdvancedForm([], $plugin_state);
      }

      $form[$type . '_wrapper']['advanced']['#prefix'] = '<div id="feeds-plugin-' . $type . '-advanced">';
      $form[$type . '_wrapper']['advanced']['#suffix'] = '</div>';

      $form_builder = FALSE;
      if ($plugin instanceof PluginFormInterface) {
        $form_builder = $plugin;
      }
      elseif ($config_form = $plugin->getConfigurationForm()) {
        $form_builder = $config_form;
      }
      if ($form_builder) {
        $plugin_form = $form_builder->buildConfigurationForm([], $plugin_state);
        $form[$type . '_configuration'] = array(
          '#type' => 'details',
          '#group' => 'plugin_settings',
          '#title' => $this->t('@type settings', array('@type' => ucfirst($type))),
        );
        $form[$type . '_configuration'] += $plugin_form;
      }
    }

    $form_state->setValue([$type . '_configuration'], $plugin_state->getValues());

    return parent::form($form, $form_state);
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
    $plugins = [];
    foreach ($this->entity->getPlugins() as $type => $plugin) {
      if ($plugin instanceof PluginFormInterface || $plugin instanceof AdvancedFormPluginInterface) {
        $plugins[$type] = $plugin;
      }
      elseif ($form = $plugin->getConfigurationForm()) {
        $plugins[$type] = $form;
      }
    }

    return $plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
    if ($form_state->getErrors()) {
      return;
    }
    $values =& $form_state->getValues();

    // Moved advanced settings to regular settings.
    foreach (array_keys($this->entity->getPlugins()) as $type) {
      if (isset($values[$type . '_wrapper']['advanced'])) {
        if (!isset($values[$type . '_configuration'])) {
          $values[$type . '_configuration'] = [];
        }
        $values[$type . '_configuration'] += $values[$type . '_wrapper']['advanced'];
      }
      unset($values[$type . '_wrapper']);
    }

    foreach ($this->getConfigurablePlugins() as $type => $plugin) {
      $plugin_state = (new FormState())->setValues($form_state->getValue([$type . '_configuration'], []));
      $plugin->validateConfigurationForm($form[$type . '_configuration'], $plugin_state);
      $form_state->setValue([$type . '_configuration'], $plugin_state->getValues());
      foreach ($plugin_state->getErrors() as $name => $error) {
        // Remove duplicate error messages.
        foreach ($_SESSION['messages']['error'] as $delta => $message) {
          if ($message['message'] === $error) {
            unset($_SESSION['messages']['error'][$delta]);
            break;
          }
        }
        $form_state->setErrorByName($name, $error);
      }
    }

    // Build the importer object from the submitted values.
    parent::validate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->getConfigurablePlugins() as $type => $plugin) {
      $plugin_state = (new FormState())->setValues($form_state->getValue([$type . '_configuration'], []));
      $plugin->submitConfigurationForm($form[$type . '_configuration'], $plugin_state);
      $form_state->setValue([$type . '_configuration'], $plugin_state->getValues());
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    $form_state->setRedirect('feeds.importer_edit', ['feeds_importer' => $this->entity->id()]);
    drupal_set_message($this->t('Your changes have been saved.'));
  }

  /**
   * Sends an ajax response.
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state) {
    $type = $form_state->getTriggeringElement()['#plugin_type'];

    $response = new AjaxResponse();

    if (isset($form[$type . '_configuration']['#id'])) {
      $hash = ltrim($form[$type . '_configuration']['#id'], '#');
      $response->addCommand(new SetHashCommand($hash));
    }
    $response->addCommand(new ReplaceCommand('#feeds-ajax-form-wrapper', drupal_render($form['plugin_settings'])));
    $response->addCommand(new ReplaceCommand('#feeds-plugin-' . $type . '-advanced', drupal_render($form[$type . '_wrapper']['advanced'])));

    return $response;
  }

}
