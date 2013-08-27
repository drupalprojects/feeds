<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Target\Path.
 */

namespace Drupal\feeds\Plugin\feeds\Target;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedsElement;
use Drupal\feeds\Plugin\ConfigurablePluginBase;
use Drupal\feeds\Plugin\TargetInterface;

/**
 * Defines a path field mapper.
 *
 * @Plugin(
 *   id = "path",
 *   title = @Translation("Path")
 * )
 */
class Path extends ConfigurablePluginBase implements TargetInterface {

  /**
   * {@inheritdoc}
   */
  public function targets() {

    $targets = array();

    switch ($this->importer->getProcessor()->entityType()) {
      case 'node':
      case 'taxonomy_term':
      case 'user':
        $targets['path_alias'] = array(
          'name' => t('Path alias'),
          'description' => t('URL path alias of the node.'),
        );
        break;
    }

    return $targets;
  }

  /**
   * {@inheritdoc}
   */
  function setTarget(FeedInterface $feed, EntityInterface $entity, $field_name, $values, array $mapping) {
    $value = '';
    foreach ($values as $value) {
      if (strlen(trim($value['value']))) {
        $value = $value['value'];
        break;
      }
    }

    $entity->path = array();

    if ($entity->id()) {
      $uri = $entity->uri();

      // Check for existing aliases.
      if ($path = path_load($uri['path'])) {
        $entity->path = $path;
      }
    }

    $entity->path['pathauto'] = FALSE;
    // Allow pathauto to set the path alias if the option is set, and this value
    // is empty.
    if ($this->getConfiguration('pathauto_override') && !$value) {
      $entity->path['pathauto'] = TRUE;
    }
    else {
      $entity->path['alias'] = ltrim($value, '/');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function summary(array $form, array $form_state, array $target) {
    if (!module_exists('pathauto')) {
      return;
    }

    if (!$this->getConfiguration('pathauto_override')) {
      return t('Do not allow Pathauto if empty.');
    }
    else {
      return t('Allow Pathauto if empty.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state, array $target = array()) {
    $form['pathauto_override'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow Pathauto to set the alias if the value is empty.'),
      '#default_value' => $this->getConfiguration('pathauto_override'),
    );

    return $form;
  }

  /**
   * {inheritdoc}
   */
  protected function getDefaultConfiguration() {
    return array('pathauto_override' => FALSE);
  }

}
