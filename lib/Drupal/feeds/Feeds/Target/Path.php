<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Target\Path.
 */

namespace Drupal\feeds\Feeds\Target;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\ImporterInterface;
use Drupal\feeds\FeedsElement;
use Drupal\feeds\Plugin\Type\ConfigurablePluginBase;
use Drupal\feeds\Plugin\Type\Target\TargetInterface;

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
  public static function targets(array &$targets, ImporterInterface $importer, array $definition) {}

  /**
   * {@inheritdoc}
   */
  public function setTarget(FeedInterface $feed, EntityInterface $entity, $field_name, $values, array $mapping) {
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
      return $this->t('Do not allow Pathauto if empty.');
    }
    else {
      return $this->t('Allow Pathauto if empty.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state, array $target = array()) {
    $form['pathauto_override'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Allow Pathauto to set the alias if the value is empty.'),
      '#default_value' => $this->getConfiguration('pathauto_override'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultConfiguration() {
    return array('pathauto_override' => FALSE);
  }

}
