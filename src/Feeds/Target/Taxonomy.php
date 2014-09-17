<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Target\Taxonomy.
 */

namespace Drupal\feeds\Feeds\Target;

use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a taxonomy term field mapper.
 *
 * @Plugin(
 *   id = "taxonomy",
 *   field_types = {"field_item:taxonomy_term_reference"}
 * )
 */
class Taxonomy extends EntityReference implements ContainerFactoryPluginInterface {

  /**
   * The taxonomy term storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $termStorage;

  /**
   * Constructs a Taxonomy object.
   *
   * @param array $settings
   *   The plugin settings.
   * @param string $plugin_id
   *   The plugin id.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $term_storage
   *   The taxonomy term storage controller.
   */
  public function __construct(array $settings, $plugin_id, array $plugin_definition, EntityStorageControllerInterface $term_storage) {
    parent::__construct($settings, $plugin_id, $plugin_definition);
    $this->termStorage = $term_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')->getStorageController('taxonomy_term')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityType() {
    return 'taxonomy_term';
  }

  /**
   * {@inheritdoc}
   */
  protected function findEntity($value) {
    if ($term_id = parent::findEntity($value)) {
      return $term_id;
    }

    $term = $this->termStorage->create(array('vid' => 'tags', 'name' => $value));
    $term->save();
    return $term->id();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array('autocreate' => FALSE) + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['autocreate'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Autocreate term'),
      '#default_value' => $this->configuration['autocreate'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = parent::getSummary();
    $create = $this->configuration['autocreate'] ? 'Yes' : 'No';

    return $summary . '<br>' . $this->t('Autocreate terms: %create', array('%create' => $create));
  }

}
