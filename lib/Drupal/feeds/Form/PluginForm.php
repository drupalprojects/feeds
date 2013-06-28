<?php

/**
 * @file
 * Contains \Drupal\feeds\Form\MappingForm.
 */

namespace Drupal\feeds\Form;

use Drupal\Core\Form\BaseFormIdInterface;
use Drupal\feeds\ImporterInterface;

/**
 * Provides a form for mapping.
 */
class PluginForm implements BaseFormIdInterface {

  /**
   * The feeds importer.
   *
   * @var \Drupal\feeds\ImporterInterface
   */
  protected $importer;

  /**
   * The feeds plugin type.
   *
   * @var string
   */
  protected $pluginType;

  /**
   * Constructs a new MappingForm object.
   *
   * @param \Drupal\feeds\ImporterInterface $importer
   *   The feeds importer.
   * @param string $plugin_type
   *   The plugin type.
   */
  public function __construct(ImporterInterface $importer, $plugin_type) {
    $this->importer = $importer;
    $this->pluginType = $plugin_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormID() {
    return 'feeds_plugin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'feeds_' . $this->pluginType . '_plugin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $definitions = \Drupal::service('plugin.manager.feeds.' . $this->pluginType)->getDefinitions();

    $importer_key = $this->importer->config[$this->pluginType]['plugin_key'];

    foreach ($definitions as $key => $plugin) {

      $form['plugin_key'][$key] = array(
        '#type' => 'radio',
        '#parents' => array('plugin_key'),
        '#title' => check_plain($plugin['title']),
        '#description' => filter_xss(isset($plugin['help']) ? $plugin['help'] : $plugin['description']),
        '#return_value' => $key,
        '#default_value' => ($key == $importer_key) ? $key : '',
      );
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    // Set the plugin and save feed.
    $this->importer->setPlugin($this->pluginType, $form_state['values']['plugin_key']);
    $this->importer->save();
    drupal_set_message(t('Changed @type plugin.', array('@type' => $this->pluginType)));
  }

}
