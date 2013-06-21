<?php

/**
 * @file
 * Contains \Drupal\feeds\Controller\ImporterController.
 */

namespace Drupal\feeds\Controller;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\feeds\Form\MappingForm;
use Drupal\feeds\Form\PluginForm;
use Drupal\feeds\Plugin\Core\Entity\Importer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for feeds module routes.
 */
class ImporterController implements ControllerInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $entityManager;

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
  public function __construct(PluginManagerInterface $entity_manager, EntityStorageControllerInterface $importer_storage) {
    $this->entityManager = $entity_manager;
    $this->importerStorage = $importer_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('plugin.manager.entity');
    return new static(
      $entity_manager,
      $entity_manager->getStorageController('feeds_importer')
    );
  }

  /**
   * Presents the importer creation form.
   *
   * @return array
   *   A form array as expected by drupal_render().
   *
   * @todo Return a render array/twig template?
   */
  public function edit(Importer $feeds_importer, $active = 'help', $plugin_type = '') {
    // Base path for changing the active container.
    $path = 'admin/structure/feeds/manage/' . $feeds_importer->id();

    $active_container = array(
      'class' => array('active-container'),
      'actions' => array(l(t('Help'), $path)),
    );
    switch ($active) {
      case 'help':
        $active_container['title'] = t('Getting started');
        $active_container['body'] = '<div class="help feeds-admin-ui">' . feeds_ui_edit_help() . '</div>';
        unset($active_container['actions']);
        break;

      case 'fetcher':
      case 'parser':
      case 'processor':
        $active_container['title'] = t('Select a !plugin_type', array('!plugin_type' => $active));
        $active_container['body'] = drupal_get_form(new PluginForm($feeds_importer, $active));
        break;

      case 'settings':
        if (!$plugin_type) {
          $active_container['title'] = t('Basic settings');
          $active_container['body'] = feeds_get_form($feeds_importer, 'configForm');
        }
        else {
          $definition = $feeds_importer->$plugin_type->getPluginDefinition();
          $active_container['title'] = t('Settings for !plugin', array('!plugin' => $definition['title']));
          $active_container['body'] = feeds_get_form($feeds_importer->$plugin_type, 'configForm');
        }
        break;

      case 'mapping':
        $definition = $feeds_importer->processor->getPluginDefinition();
        $active_container['title'] = t('Mapping for !processor', array('!processor' => $definition['title']));
        $active_container['body'] = drupal_get_form(new MappingForm($feeds_importer));
        break;
    }

    // Build config info.
    $config_info = $info = array();
    $info['class'] = array('config-set');

    // Basic information.
    $items = array();
    if ($feeds_importer->config['import_period'] == FEEDS_SCHEDULE_NEVER) {
      $import_period = t('off');
    }
    elseif ($feeds_importer->config['import_period'] == 0) {
      $import_period = t('as often as possible');
    }
    else {
      $import_period = t('every !interval', array('!interval' => format_interval($feeds_importer->config['import_period'])));
    }
    $items[] = t('Periodic import: !import_period', array('!import_period' => $import_period));
    $items[] = $feeds_importer->config['import_on_create'] ? t('Import on submission') : t('Do not import on submission');

    $info['title'] = t('Basic settings');
    $info['body'] = array(
      array(
        'body' => theme('item_list', array('items' => $items)),
        'actions' => array(l(t('Settings'), $path . '/settings')),
      ),
    );
    $config_info[] = $info;

    // Fetcher.
    $fetcher_definition = $feeds_importer->fetcher->getPluginDefinition();
    $actions = array();
    if (feeds_get_form($feeds_importer->fetcher, 'configForm')) {
      $actions = array(l(t('Settings'), $path . '/settings/fetcher'));
    }
    $info['title'] = t('Fetcher');
    $info['body'] = array(
      array(
        'title' => $fetcher_definition['title'],
        'body' => $fetcher_definition['description'],
        'actions' => $actions,
      ),
    );
    $info['actions'] = array(l(t('Change'), $path . '/fetcher'));
    $config_info[] = $info;

    // Parser.
    $parser_definition = $feeds_importer->parser->getPluginDefinition();
    $actions = array();
    if (feeds_get_form($feeds_importer->parser, 'configForm')) {
      $actions = array(l(t('Settings'), $path . '/settings/parser'));
    }
    $info['title'] = t('Parser');
    $info['body'] = array(
      array(
        'title' => $parser_definition['title'],
        'body' => $parser_definition['description'],
        'actions' => $actions,
      )
    );
    $info['actions'] = array(l(t('Change'), $path . '/parser'));
    $config_info[] = $info;

    // Processor.
    $actions = array();
    $processor_definition = $feeds_importer->processor->getPluginDefinition();
    if (feeds_get_form($feeds_importer->processor, 'configForm')) {
      $actions[] = l(t('Settings'), $path . '/settings/processor');
    }
    $actions[] = l(t('Mapping'), $path . '/mapping');
    $info['title'] = t('Processor');
    $info['body'] = array(
      array(
        'title' => $processor_definition['title'],
        'body' => $processor_definition['description'],
        'actions' => $actions,
      )
    );
    $info['actions'] = array(l(t('Change'), $path . '/processor'));
    $config_info[] = $info;

    return theme('feeds_ui_edit_page', array(
      'info' => $config_info,
      'active' => $active_container,
    ));
  }

}
