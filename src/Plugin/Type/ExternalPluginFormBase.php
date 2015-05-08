<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\Type\ExternalPluginFormBase.
 */

namespace Drupal\feeds\Plugin\Type;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\feeds\Plugin\Type\FeedsPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Feeds plugins that have external configuration forms.
 */
abstract class ExternalPluginFormBase implements ExternalPluginFormInterface {
  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * The Feeds plugin.
   *
   * @var \Drupal\feeds\Plugin\Type\FeedsPluginInterface
   */
  protected $plugin;

  /**
   * Constructs an ExternalPluginFormBase object.
   */
  public function __construct(FeedsPluginInterface $plugin) {
    $this->plugin = $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, FeedsPluginInterface $plugin) {
    return new static($plugin);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Validation is optional.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->plugin->setConfiguration($form_state->getValues());
  }

}
