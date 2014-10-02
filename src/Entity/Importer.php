<?php

/**
 * @file
 * Contains \Drupal\feeds\Entity\Importer.
 */

namespace Drupal\feeds\Entity;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Utility\String;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\DefaultSinglePluginBag;
use Drupal\feeds\Event\ClearEvent;
use Drupal\feeds\Event\EventDispatcherTrait;
use Drupal\feeds\Event\ExpireEvent;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\FetchEvent;
use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\Event\ProcessEvent;
use Drupal\feeds\ImporterInterface;
use Drupal\feeds\Plugin\Type\ClearableInterface;
use Drupal\feeds\Plugin\Type\LockableInterface;
use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;

/**
 * Defines the feeds importer entity.
 *
 * @ConfigEntityType(
 *   id = "feeds_importer",
 *   label = @Translation("Feed importer"),
 *   module = "feeds",
 *   handlers = {
 *     "storage" = "Drupal\feeds\ImporterStorageController",
 *     "access" = "Drupal\feeds\ImporterAccessController",
 *     "list_builder" = "Drupal\feeds\ImporterListController",
 *     "form" = {
 *       "create" = "Drupal\feeds\ImporterFormController",
 *       "edit" = "Drupal\feeds\ImporterFormController",
 *       "delete" = "Drupal\feeds\Form\ImporterDeleteForm"
 *     }
 *   },
 *   config_prefix = "importer",
 *   bundle_of = "feeds_feed",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "status" = "status"
 *   },
 *   links = {
 *     "edit-form" = "feeds.importer_edit",
 *     "delete-form" = "feeds.importer_delete"
 *   }
 * )
 */
class Importer extends ConfigEntityBundleBase implements ImporterInterface {
  use EventDispatcherTrait;

  /**
   * The importer ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the importer.
   *
   * @var string
   */
  public $label;

  /**
   * Description of the importer.
   *
   * @var string
   */
  public $description;

  /**
   * The types of plugins we support.
   *
   * @var array
   *
   * @todo Make this dynamic?
   */
  protected $pluginTypes = array(
    'scheduler',
    'fetcher',
    'parser',
    'processor',
  );

  /**
   * Plugin ids and configuration.
   */
  protected $plugins = array(
    'scheduler' => array(
      'id' => 'periodic',
      'configuration' => array(),
    ),
    'fetcher' => array(
      'id' => 'http',
      'configuration' => array(),
    ),
    'parser' => array(
      'id' => 'syndication',
      'configuration' => array(),
    ),
    'processor' => array(
      'id' => 'entity:node',
      'configuration' => array(),
    ),
  );

  /**
   * The list of source to target mappings.
   *
   * @var array
   */
  protected $mappings = array();

  /**
   * The list of sources.
   *
   * @var array
   */
  protected $sources;

  /**
   * The list of targets;
   *
   * @var array
   */
  protected $targets;

  /**
   * The plugin bags that store feeds plugins keyed by plugin type.
   *
   * These are lazily instantiated on-demand.
   *
   * @var \Drupal\Core\Plugin\DefaultSinglePluginBag[]
   */
  protected $pluginBags = array();

  protected $targetPlugins = array();

  protected $sourcePlugins = array();

  /**
   * Constructs a new Importer object.
   */
  public function __construct(array $values, $entity_type) {

    // Move plugin configuration separately from values.
    foreach ($this->getPluginTypes() as $type) {
      if (isset($values[$type])) {
        $this->plugins[$type] = $values[$type];
        unset($values[$type]);
      }
    }

    parent::__construct($values, $entity_type);

    // Prepare plugin bags. This has to be done after all configuration is done.
    foreach ($this->getPluginTypes() as $type) {
      $this->initPluginBag($type);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    foreach ($this->getPlugins() as $plugin) {
      if ($plugin instanceof LockableInterface && $plugin->isLocked()) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getImportPeriod() {
    return (int) $this->getPlugin('scheduler')->getImportPeriod();
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingSources() {
    if ($this->sources === NULL) {
      $this->sources = (array) $this->getParser()->getMappingSources();
      $definitions = \Drupal::service('plugin.manager.feeds.source')->getDefinitions();

      foreach ($definitions as $definition) {
        $class = $definition['class'];
        $class::sources($this->sources, $this, $definition);
      }

      \Drupal::moduleHandler()->alter('feeds_sources', $this->sources, $this);
    }

    return $this->sources;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingTargets() {
    if ($this->targets === NULL) {
      $this->targets = $this->getProcessor()->getMappingTargets();
      $definitions = \Drupal::service('plugin.manager.feeds.target')->getDefinitions();

      foreach ($definitions as $definition) {
        $class = $definition['class'];
        $class::targets($this->targets, $this, $definition);
      }

      \Drupal::moduleHandler()->alter('feeds_targets', $this->targets, $this);
    }

    return $this->targets;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappings() {
    return $this->mappings;
  }

  public function getMappingsBag() {
    return $this->mappingsBag;
  }

  /**
   * {@inheritdoc}
   */
  public function setMappings(array $mappings) {
    $this->mappings = $mappings;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addMapping(array $mapping) {
    $this->mappings[] = $mapping;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMapping($delta) {
    return $this->mappings[$delta];
  }

  /**
   * {@inheritdoc}
   */
  public function setMapping($delta, $mapping) {
    $this->mappings[$delta]['map'] = $mapping['map'];
    if (!empty($mapping['unique'])) {
      $this->mappings[$delta]['unique'] = array_filter($mapping['unique']);
    }
    return $this;
  }

  public function removeMapping($delta) {
    unset($this->mappings[$delta]);
    unset($this->targetPlugins[$delta]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeMappings() {
    $this->mappings = array();
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginTypes() {
    return $this->pluginTypes;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugins() {
    $plugins = array();
    foreach ($this->getPluginTypes() as $type) {
      $plugins[$type] = $this->getPlugin($type);
    }

    return $plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function getFetcher() {
    return $this->getPlugin('fetcher');
  }

  /**
   * {@inheritdoc}
   */
  public function getParser() {
    return $this->getPlugin('parser');
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessor() {
    return $this->getPlugin('processor');
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin($plugin_type) {
    return $this->pluginBags[$plugin_type]->get($this->plugins[$plugin_type]['id']);
  }

  /**
   * {@inheritdoc}
   *
   * @todo Use plugin bag.
   */
  public function getTargetPlugin($delta) {
    if (isset($this->targetPlugins[$delta])) {
      return $this->targetPlugins[$delta];
    }

    $targets = $this->getMappingTargets();
    $target = $this->mappings[$delta]['target'];

    // The target is a plugin.
    $id = $targets[$target]->getPluginId();

    $configuration = [];
    $configuration['importer'] = $this;
    $configuration['target_definition'] = $targets[$target];
    if (isset($this->mappings[$delta]['settings'])) {
      $configuration += $this->mappings[$delta]['settings'];
    }
    $this->targetPlugins[$delta] = \Drupal::service('plugin.manager.feeds.target')->createInstance($id, $configuration);

    return $this->targetPlugins[$delta];
  }

  /**
   * {@inheritdoc}
   *
   * @todo Use plugin bag.
   */
  public function getSourcePlugin($source) {
    if (!isset($this->sourcePlugins[$source])) {
      $sources = $this->getMappingSources();

      // The source is a plugin.
      if (isset($sources[$source]['id'])) {
        $configuration = array('importer' => $this);
        $this->sourcePlugins[$source] = \Drupal::service('plugin.manager.feeds.source')->createInstance($sources[$source]['id'], $configuration);
      }
      else {
        $this->sourcePlugins[$source] = FALSE;
      }
    }

    return $this->sourcePlugins[$source];
  }

  public function getPluginOptionsList($plugin_type) {
    $manager = \Drupal::service("plugin.manager.feeds.$plugin_type");

    $options = array();
    foreach ($manager->getDefinitions() as $id => $definition) {
      $options[$id] = String::checkPlain($definition['title']);
    }

    return $options;
  }

  /**
   * Initializes a plugin bag for a plugin type.
   *
   * @param string $plugin_type
   *   The plugin type to initialize.
   */
  protected function initPluginBag($plugin_type) {
    $id = $this->plugins[$plugin_type]['id'];

    $configuration = array('importer' => $this);
    if (isset($this->plugins[$plugin_type]['configuration'])) {
      $configuration += $this->plugins[$plugin_type]['configuration'];
    }

    $manager = \Drupal::service("plugin.manager.feeds.$plugin_type");

    $this->pluginBags[$plugin_type] = new DefaultSinglePluginBag($manager, $id, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function setPlugin($plugin_type, $plugin_id) {
    $this->plugins[$plugin_type]['id'] = $plugin_id;
    $this->pluginBags[$plugin_type]->addInstanceID($plugin_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function uri() {
    return array(
      'path' => 'admin/structure/feeds/manage/' . $this->id(),
      'options' => array(
        'entity_type' => $this->entityType,
        'entity' => $this,
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage_controller, $update = TRUE) {
    parent::preSave($storage_controller);

    foreach ($this->getPlugins() as $type => $plugin) {
      $plugin->onImporterSave($update);
    }

    foreach ($this->getPlugins() as $type => $plugin) {
      // If this plugin has any configuration, ensure that it is set.
      if ($plugin instanceof ConfigurablePluginInterface) {
        $this->plugins[$type]['configuration'] = $plugin->getConfiguration();
      }
      else {
        unset($this->plugins[$type]['configuration']);
      }
    }

    foreach ($this->targetPlugins as $delta => $target_plugin) {
      if ($target_plugin instanceof ConfigurableTargetInterface) {
        $this->mappings[$delta]['settings'] = $target_plugin->getConfiguration();
      }
      else {
        unset($this->mappings[$delta]['settings']);
      }
    }

    $this->mappings = array_values($this->mappings);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    foreach ($entities as $entity) {
      foreach ($entity->getPlugins() as $plugin) {
        $plugin->onImporterDelete();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    $properties = parent::toArray();
    $properties += $this->plugins;
    $properties['mappings'] = $this->mappings;
    return $properties;
  }

}
