<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Target\EntityReference.
 */

namespace Drupal\feeds\Feeds\Target;

use Drupal\Component\Utility\String as DrupalString;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines an entity reference mapper.
 *
 * @FeedsTarget(
 *   id = "entity_reference",
 *   field_types = {"entity_reference"},
 *   arguments = {"@entity.manager", "@entity.query"}
 * )
 */
class EntityReference extends FieldTargetBase implements ConfigurableTargetInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The entity query factory object.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Constructs an EntityReference object.
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
    $this->entityManager = $entity_manager;
    $this->queryFactory = $query_factory;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    return FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('target_id');
  }

  protected function getPotentialFields() {
    $field_definitions = $this->entityManager->getBaseFieldDefinitions($this->getEntityType());
    $field_definitions = array_filter($field_definitions, [$this, 'filterFieldTypes']);
    $options = [];
    foreach ($field_definitions as $id => $definition) {
      $options[$id] = DrupalString::checkPlain($definition->getLabel());
    }

    return $options;
  }

  protected function filterFieldTypes($field) {
    if ($field->isComputed()) {
      return FALSE;
    }

    switch ($field->getType()) {
      case 'string':
      case 'text_long':
      case 'path':
      case 'uuid':
        return TRUE;

      default:
        return FALSE;
    }
  }

  protected function getEntityType() {
    return $this->settings['target_type'];
  }

  protected function getBundle() {
    return $this->settings['target_bundle'];
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    if ($target_id = $this->findEntity($values['target_id'], $this->configuration['reference_by'])) {
      $values['target_id'] = $target_id;
      return;
    }

    throw new EmptyFeedException();
  }

  /**
   * Searches for an entity by entity key.
   *
   * @param string $value
   *   The value to search for.
   *
   * @return int|bool
   *   The entity id, or false, if not found.
   */
  protected function findEntity($value, $field) {
    $query = $this->queryFactory->get($this->getEntityType());

    if ($bundle = $this->getBundle()) {
      $bundle_key = $this->entityManager
        ->getStorage($this->getEntityType())
        ->getEntityType()
        ->getKey('bundle');
      $query->condition($bundle_key, $bundle);
    }

    $ids = array_filter($query->condition($field, $value)->range(0, 1)->execute());
    if ($ids) {
      return reset($ids);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['reference_by' => NULL];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = $this->getPotentialFields();

    $form['reference_by'] = [
      '#type' => 'select',
      '#title' => $this->t('Reference by'),
      '#options' => $options,
      '#default_value' => $this->configuration['reference_by'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $options = $this->getPotentialFields();
    if ($this->configuration['reference_by'] && isset($options[$this->configuration['reference_by']])) {
      $options = $this->getPotentialFields();
      return $this->t('Reference by: %message', ['%message' => $options[$this->configuration['reference_by']]]);
    }
    return $this->t('Please select a field to reference by.');
  }

}
