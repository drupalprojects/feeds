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
use Drupal\feeds\Entity\Importer;
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
        $active_container['body'] = '<div class="help feeds-admin-ui">' . $this->help() . '</div>';
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
          $active_container['body'] = $this->entityManager->getForm($feeds_importer);
        }
        elseif ($feeds_importer->$plugin_type instanceof FormInterface) {
          $definition = $feeds_importer->$plugin_type->getPluginDefinition();
          $active_container['title'] = t('Settings for !plugin', array('!plugin' => $definition['title']));
          $active_container['body'] = drupal_get_form($feeds_importer->$plugin_type);
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

    foreach ($feeds_importer->getPluginTypes() as $type) {
      $plugin_definition = $feeds_importer->$type->getPluginDefinition();
      $actions = array();
      if ($feeds_importer->$type instanceof FormInterface) {
        $actions = array(l(t('Settings'), "$path/settings/$type"));
      }
      if ($type == 'processor') {
        $actions[] = l(t('Mapping'), "$path/mapping");
      }
      $info['title'] = t(ucfirst($type));
      $info['body'] = array(
        array(
          'title' => $plugin_definition['title'],
          'body' => $plugin_definition['description'],
          'actions' => $actions,
        ),
      );
      $info['actions'] = array(l(t('Change'), "$path/$type"));
      $config_info[] = $info;
    }

    return theme('feeds_edit_page', array(
      'info' => $config_info,
      'active' => $active_container,
    ));
  }

  /**
   * Introductory help for admin/structure/feeds/manage/%feeds_importer page
   */
  protected function help() {
    return t('
      <p>
      You can create as many Feeds importer configurations as you would like to. Each can have a distinct purpose like letting your users aggregate RSS feeds or importing a CSV file for content migration. Here are a couple of things that are important to understand in order to get started with Feeds:
      </p>
      <ul>
      <li>
      Every importer configuration consists of basic settings, a fetcher, a parser and a processor and their settings.
      </li>
      <li>
      The <strong>basic settings</strong> define the general behavior of the importer. <strong>Fetchers</strong> are responsible for loading data, <strong>parsers</strong> for organizing it and <strong>processors</strong> for "doing stuff" with it, usually storing it.
      </li>
      <li>
      In Basic settings, you can <strong>attach an importer configuration to a content type</strong>. This is useful when many imports of a kind should be created, for example in an RSS aggregation scenario. If you don\'t attach a configuration to a content type, you can use it on the !import page.
      </li>
      <li>
      Imports can be <strong>scheduled periodically</strong> - see the periodic import select box in the Basic settings.
      </li>
      <li>
      Processors can have <strong>mappings</strong> in addition to settings. Mappings allow you to define what elements of a data feed should be mapped to what content fields on a granular level. For instance, you can specify that a feed item\'s author should be mapped to a node\'s body.
      </li>
      </ul>
      ', array('!import' => l(t('Import'), 'import')));
  }

}
