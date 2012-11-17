<?php
/**
 * @file
 * Class definition of FeedsEntityProcessor.
 */

/**
 * Creates entities from feed items.
 */
class FeedsEntityProcessor extends FeedsProcessor {

  /**
   * Returns the plugin definition.
   */
  protected function pluginDefinition() {
    $importer = feeds_importer($this->id);
    $plugin_key = $importer->config['processor']['plugin_key'];
    return ctools_get_plugins('feeds', 'plugins', $plugin_key);
  }

  /**
   * Define entity type.
   */
  public function entityType() {
    $plugin_info = $this->pluginDefinition();
    return $plugin_info['type'];
  }

  /**
   * Implements parent::entityInfo().
   */
  protected function entityInfo() {
    $info = parent::entityInfo();
    $importer = feeds_importer($this->id);
    $plugin_key = $importer->config['processor']['plugin_key'];
    $plugin_info = $this->pluginDefinition();
    if (isset($info['label plural'])) {
      $plural = $info['label plural'];
    }
    else {
      $plural = $info['label'];
    }

    $info += array('label plural' => $plural);
    return $info;
  }

  /**
   * Creates a new entity in memory and returns it.
   */
  protected function newEntity(FeedsSource $source) {
    $entity = entity_property_values_create_entity($this->entityType(), $this->config['values'])->value();
    return $entity;
  }

  /**
   * Save a entity.
   */
  public function entitysave($entity) {
    entity_save($this->entityType(), $entity);
  }

  /**
   * Delete a series of entities.
   */
  protected function entityDeleteMultiple($ids) {
    entity_delete_multiple($this->entityType(), $ids);
  }
  /**
   * Override parent::configDefaults().
   */
  public function configDefaults() {
    return array(
      'values' => array(),
    ) + parent::configDefaults();
  }

  /**
   * Override parent::configForm().
   */
  public function configForm(&$form_state) {
    $form = parent::configForm($form_state);

    $form['values'] = array(
      '#type' => 'fieldset',
      '#title' => t('Default values'),
      '#tree' => TRUE,
      '#description' => t('Most of the values below can be overriden by mapping a value.'),
    );

    $entity_info = $this->entityInfo();
    $label_plural = $entity_info['label plural'];

    $form['input_format']['#description'] = t('Select the default input format for the %entity to be created.', array('%entity' => $label_plural));

    $wrapper = entity_metadata_wrapper($this->entityType());

    foreach ($wrapper->getPropertyInfo() as $name => $property) {
      if (!empty($property['setter callback']) && empty($property['field'])) {
        $form['values'][$name] = array(
          '#type' => 'textfield',
          '#title' => $property['label'],
          '#description' => isset($property['description']) ? $property['description'] : '',
          '#default_value' => isset($this->config['values'][$name]) ? $this->config['values'][$name] : NULL,
          '#required' => !empty($property['required']),
        );

        if (!empty($property['options list'])) {
          $form['values'][$name]['#type'] = 'select';
          if (isset($property['type']) && entity_property_list_extract_type($property['type'])) {
            $form['values'][$name]['#type'] = 'checkboxes';
            if (!is_array($form['values'][$name]['#default_value'])) {
              $form['values'][$name]['#default_value'] = array($form['values'][$name]['#default_value']);
            }
          }
          $form['values'][$name]['#options'] = $wrapper->$name->optionsList();
        }

        elseif (!empty($property['type']) && $property['type'] == 'boolean') {
          $form['values'][$name]['#type'] = 'checkbox';
        }
        // elseif (!empty($property['type']) && $property['type'] == 'date') {
        //   $form['values'][$name]['#type'] = 'date';
        // }
      }
    }
    return $form;
  }

  /**
   * Override parent::configFormValidate().
   */
  public function configFormValidate(&$values) {

    $form = parent::configFormValidate($values);

    $wrapper = entity_metadata_wrapper($this->entityType());

    foreach ($wrapper->getPropertyInfo() as $name => $property) {
      if (!empty($property['setter callback']) && empty($property['field'])) {

        // Entity api won't accept empty date values.
        if (!empty($property['type']) && $property['type'] == 'date') {
          if (empty($values['values'][$name])) {
            unset($values['values'][$name]);
            continue;
          }
        }

        if (isset($property['type']) && array_key_exists($name, $values['values'])) {
          if (entity_property_list_extract_type($property['type']) && !is_array($values['values'][$name])) {
            $values['values'][$name] = array($values['values'][$name]);
          }
          $valid = entity_property_verify_data_type($values['values'][$name], $property['type']);
          if (!$valid) {
            form_set_error("values][$name", t('Invalid data value given. Be sure it matches the required data type and format.'));
          }
        }
      }
    }
  }

  /**
   * Return available mapping targets.
   */
  public function getMappingTargets() {
    // Get a wrapper with the right bundle info.

    $targets = parent::getMappingTargets();

    $entity_info = $this->entityInfo();
    $bundle = $this->entityType();
    if (isset($entity_info['bundle keys']['bundle'])) {
      $bundle = $this->config['values'][$entity_info['bundle keys']['bundle']];
    }
    $info = array('bundle' => $bundle);

    $wrapper = entity_metadata_wrapper($this->entityType(), NULL, $info);
    // @todo: maybe restrict to data types feeds can deal with.
    foreach ($wrapper->getPropertyInfo() as $name => $property) {
      if (!empty($property['setter callback']) && empty($property['field'])) {
        $targets[$name] = array(
          'name' => $property['label'],
          'description' => isset($property['description']) ? $property['description'] : NULL,
        );
      }
    }
    $targets[$entity_info['entity keys']['id']]['optional_unique'] = TRUE;

    // Remove the bundle target.
    if (isset($entity_info['bundle keys']['bundle'])) {
      unset($targets[$entity_info['bundle keys']['bundle']]);
    }

    // Let other modules expose mapping targets.
    self::loadMappers();
    $type = $this->entityType();
    drupal_alter('feeds_processor_targets', $targets, $type, $info['bundle']);

    return $targets;
  }
}
