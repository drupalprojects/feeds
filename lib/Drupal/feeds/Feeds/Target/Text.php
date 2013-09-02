<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Target\Text.
 */

namespace Drupal\feeds\Feeds\Target;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;

/**
 * Defines a text field mapper.
 *
 * @Plugin(
 *   id = "text",
 *   title = @Translation("Text"),
 *   field_types = {"list_text", "text", "text_long", "text_with_summary"}
 * )
 */
class Text extends String implements ConfigurableTargetInterface {

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(array &$target) {
    unset($target['properties']['format']);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    $values['value'] = (string) $values['value'];
    $values['format'] = $this->configuration['format'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultConfiguration() {
    return array('format' => 'plain_text');
  }

  /**
   * {@inheritdoc}
   *
   * @todo Inject $user.
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    global $user;
    $options = array();
    foreach (filter_formats($user) as $id => $format) {
      $options[$id] = $format->label();
    }

    $form['format'] = array(
      '#type' => 'select',
      '#title' => $this->t('Filter format'),
      '#options' => $options,
      '#default_value' => $this->configuration['format'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $formats = \Drupal::entityManager()
      ->getStorageController('filter_format')
      ->loadByProperties(array('status' => '1', 'format' => $this->configuration['format']));
    if ($formats) {
      $format = reset($formats);
      return $this->t('Format: %format', array('%format' => $format->label()));
    }
  }

}
