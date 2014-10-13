<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Target\Taxonomy.
 */

namespace Drupal\feeds\Feeds\Target;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a taxonomy term field mapper.
 *
 * @FeedsTarget(
 *   id = "taxonomy",
 *   field_types = {"taxonomy_term_reference"}
 * )
 */
class Taxonomy extends EntityReference implements ContainerFactoryPluginInterface {

  /**
   * The taxonomy term storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $termStorage;

  /**
   * Constructs a Taxonomy object.
   *
   * @param array $settings
   *   The plugin settings.
   * @param string $plugin_id
   *   The plugin id.
   * @param \Drupal\Core\Entity\EntityStorageInterface $term_storage
   *   The taxonomy term storage controller.
   */
  public function __construct(array $settings, $plugin_id, array $plugin_definition, EntityStorageInterface $term_storage) {
    parent::__construct($settings, $plugin_id, $plugin_definition);
    $this->termStorage = $term_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')->getStorage('taxonomy_term')
    );
  }

  protected function getBundle() {
    return $this->settings['allowed_values'][0]['vocabulary'];
  }

  /**
   * {@inheritdoc}
   */
  protected function findEntity($value, $reference_by) {
    if ($reference_by === 'name') {
      $value = trim($value);
      if (!strlen($value)) {
        return FALSE;
      }
    }

    if ($term_id = parent::findEntity($value, $reference_by)) {
      return $term_id;
    }

    if (!$this->configuration['autocreate'] || $reference_by !== 'name') {
      return FALSE;
    }

    $term = $this->termStorage->create([
      'vid' => $this->settings['allowed_values'][0]['vocabulary'],
      'name' => $value,
    ]);
    $term->save();
    return $term->id();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'autocreate' => FALSE,
      'reference_by' => 'name',
    ] + parent::defaultConfiguration();
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
    $create = $this->configuration['autocreate'] ? $this->t('Yes') : $this->t('No');

    return $summary . '<br>' . $this->t('Autocreate terms: %create', ['%create' => $create]);
  }

}
