<?php

/**
 * @file
 * Contains \Drupal\feeds\Controller\ImporterController.
 */

namespace Drupal\feeds\Controller;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\feeds\Form\MappingForm;
use Drupal\feeds\Form\PluginForm;
use Drupal\feeds\ImporterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for feeds module routes.
 */
class ImporterController implements FormInterface, ControllerInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $entityManager;

  /**
   * The plugin managers keyed by plugin type.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface[]
   */
  protected $managers = array();

  /**
   * The importer storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $importerStorage;

  /**
   * Constructs a \Drupal\feeds\Controller\ImporterController object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $entity_manager
   *   The Entity manager.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $importer_storage
   *   The feed importer controller.
   */
  public function __construct(
    PluginManagerInterface $entity_manager,
    EntityStorageControllerInterface $importer_storage,
    PluginManagerInterface $fetcher_manager,
    PluginManagerInterface $parser_manager,
    PluginManagerInterface $processer_manager) {

    $this->entityManager = $entity_manager;
    $this->importerStorage = $importer_storage;
    $this->managers['fetcher'] = $fetcher_manager;
    $this->managers['parser'] = $parser_manager;
    $this->managers['processor'] = $processer_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('plugin.manager.entity');
    return new static(
      $entity_manager,
      $entity_manager->getStorageController('feeds_importer'),
      $container->get('plugin.manager.feeds.fetcher'),
      $container->get('plugin.manager.feeds.parser'),
      $container->get('plugin.manager.feeds.processor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'feeds_importer_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, ImporterInterface $feeds_importer = NULL) {
    foreach ($feeds_importer->getPluginTypes() as $type) {
      $definitions = $this->managers[$type]->getDefinitions();

      $options = array();
      foreach ($definitions as $key => $definition) {
        $options[$key] = check_plain($definition['title']);
      }

      $form[$type] = array(
        '#type' => 'select',
        '#title' => t(ucfirst($type)),
        '#options' => $options,
        '#default_value' => $feeds_importer->$type->getPluginID(),
      );
    }

    return $form;
  }

  public function validateForm(array &$form, array &$form_state) {
  }

  public function submitForm(array &$form, array &$form_state) {
  }

}
