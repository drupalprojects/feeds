<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Target\Taxonomy.
 */

namespace Drupal\feeds\Feeds\Target;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a taxonomy term field mapper.
 *
 * @FeedsTarget(
 *   id = "taxonomy",
 *   field_types = {"taxonomy_term_reference"},
 *   arguments = {"@entity.manager", "@entity.query"}
 * )
 */
class Taxonomy extends EntityReference {

  /**
   * The term storage controller.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * Constructs an Taxonomy object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityManagerInterface $entity_manager, QueryFactory $query_factory) {
    $this->termStorage = $entity_manager->getStorage('taxonomy_term');
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager, $query_factory);
  }

  /**
   * {@inheritdoc}
   */
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

    $form['autocreate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autocreate term'),
      '#default_value' => $this->configuration['autocreate'],
    ];

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
