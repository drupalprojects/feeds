<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Target\Text.
 */

namespace Drupal\feeds\Feeds\Target;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a text field mapper.
 *
 * @FeedsTarget(
 *   id = "text",
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary"
 *   }
 * )
 */
class Text extends String implements ConfigurableTargetInterface, ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    $definition = FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('value');

    if ($field_definition->getType() === 'text_with_summary') {
      $definition->addProperty('summary');
    }
    return $definition;
  }

  /**
   * Constructs a Text object.
   *
   * @param array $settings
   *   The plugin settings.
   * @param string $plugin_id
   *   The plugin id.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   */
  public function __construct(array $settings, $plugin_id, array $plugin_definition, AccountInterface $user) {
    parent::__construct($settings, $plugin_id, $plugin_definition);
    $this->user = $user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $settings, $plugin_id, $plugin_definition) {
    return new static(
      $settings,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    // At todo. Maybe break these up into separate classes.
    parent::prepareValue($delta, $values);

    $values['format'] = $this->configuration['format'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array('format' => 'plain_text');
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = array();
    foreach (filter_formats($this->user) as $id => $format) {
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
      ->getStorage('filter_format')
      ->loadByProperties(array('status' => '1', 'format' => $this->configuration['format']));
    if ($formats) {
      $format = reset($formats);
      return $this->t('Format: %format', array('%format' => $format->label()));
    }
  }

}
