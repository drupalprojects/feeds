<?php

/**
 * @file
 * Contains \Drupal\feeds\Form\ImporterAddForm.
 */

namespace Drupal\feeds\Form;

use Drupal\Core\Form\FormInterface;

/**
 * Provides a form for deleting a feed.
 */
class ImporterAddForm implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'feeds_importer_add';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#description' => t('A natural name for this configuration. Example: RSS Feed. You can always change this name later.'),
      '#required' => TRUE,
      '#maxlength' => 128,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#required' => TRUE,
      '#maxlength' => 128,
      '#machine_name' => array(
        'exists' => array($this, 'exists'),
        'source' => array('name'),
      ),
    );
    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => t('Description'),
      '#description' => t('A description of this configuration.'),
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Create'),
    );
    return $form;
  }

  public function exists($importer_id) {
    return entity_load('feeds_importer', $importer_id);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    if (!empty($form_state['values']['id'])) {
      $importer = entity_create('feeds_importer', array('id' => $form_state['values']['id']));
      $importer->configFormValidate($form_state['values']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    // Create feed.
    $importer = entity_create('feeds_importer', array(
      'id' => $form_state['values']['id'],
      'name' => $form_state['values']['name'],
      'description' => $form_state['values']['description'],
    ));

    // In any case, we want to set this configuration's title and description.
    $importer->save();

    // Set a message and redirect to settings form.
    drupal_set_message(t('Your configuration has been created with default settings. If they do not fit your use case you can adjust them here.'));
    $form_state['redirect'] = 'admin/structure/feeds/manage/' . $importer->id();
  }

}
