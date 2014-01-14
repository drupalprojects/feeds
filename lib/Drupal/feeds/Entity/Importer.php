<?php

/**
 * @file
 * Contains \Drupal\feeds\Entity\Importer.
 */

namespace Drupal\feeds\Entity;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\DefaultSinglePluginBag;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\feeds\ImporterInterface;
use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;

/**
 * Defines the feeds importer entity.
 *
 * @EntityType(
 *   id = "feeds_importer",
 *   label = @Translation("Feed importer"),
 *   module = "feeds",
 *   controllers = {
 *     "storage" = "Drupal\feeds\ImporterStorageController",
 *     "access" = "Drupal\feeds\ImporterAccessController",
 *     "list" = "Drupal\feeds\ImporterListController",
 *     "form" = {
 *       "edit" = "Drupal\feeds\ImporterFormController",
 *       "delete" = "Drupal\feeds\Form\ImporterDeleteForm"
 *     }
 *   },
 *   config_prefix = "feeds.importer",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "status" = "status"
 *   },
 *   bundle_of = "feeds_feed",
 *   links = {
 *     "edit-form" = "admin/structure/feeds/manage/{feeds_importer}"
 *   }
 * )
 */
class Importer extends ConfigEntityBase implements ImporterInterface {

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
    'manager',
    'scheduler',
    'fetcher',
    'parser',
    'processor',
  );

  /**
   * Plugin ids and configuration.
   */
  protected $plugins = array(
    'manager' => array(
      'id' => 'background',
      'configuration' => array(),
    ),
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
   * @var \Drupal\Component\Plugin\DefaultSinglePluginBag[]
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
  public function getLimit() {
    return $this->getProcessor()->getLimit();
  }

  /**
   * {@inheritdoc}
   */
  public function getImportPeriod() {
    // Check whether any fetcher is overriding the import period.
    $period = $this->getPlugin('scheduler')->getImportPeriod();

    // Allow fetcher to override the import period.
    $fetcher = $this->getFetcher();
    if ($fetcher instanceof PuSHFetcherInterface && $fetcher_period = $fetcher->importPeriod($feed)) {
      $period = $fetcher_period;
    }

    return $period;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingSources() {
    if ($this->sources === NULL) {
      $this->sources = $this->getParser()->getMappingSources();
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
    if (!$this->mappings) {
      $this->mappings = $this->buildDefaultMappings();
    }

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
  }

  public function addMapping(array $mapping) {
    $this->mappings[] = $mapping;
  }

  public function getMapping($delta) {
    return $this->mappings[$delta];
  }

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
   * Builds a default mapping configuration based off of suggestions.
   *
   * @return array
   *   The mapping array.
   *
   * @todo Make this work again.
   */
  protected function buildDefaultMappings() {
    return array();
    $mappings = array();
    $targets = $this->getMappingTargets();
    $sources = $this->getMappingSources();

    // // Remove sources with no suggestions.
    $suggested = array();
    foreach ($sources as $source_id => $source) {
      if (!empty($source['suggestions']) && !empty($source['suggestions']['targets'])) {
        $suggested[$source_id] = $source;
      }
    }

    if ($suggested) {
      foreach ($targets as $target_id => $target) {
        foreach ($suggested as $source_id => $source) {
          if (in_array($target_id, $source['suggestions']['targets'])) {
            $mappings[] = array('source' => $source_id, 'target' => $target_id);
            unset($targets[$target_id]);
            unset($sources[$source_id]);
            unset($suggested[$source_id]);
            break;
          }
        }
      }
    }

    $suggested = array();
    foreach ($sources as $source_id => $source) {
      if (!empty($source['suggestions']) && !empty($source['suggestions']['types'])) {
        $suggested[$source_id] = $source;
      }
    }

    if ($suggested) {
      foreach ($targets as $target_id => $target) {

        if (!isset($target['type'])) {
          continue;
        }

        foreach ($suggested as $source_id => $source) {
          if (isset($source['suggestions']['types'][$target['type']])) {
            foreach ($source['suggestions']['types'][$target['type']] as $key => $value) {
              if (isset($target['settings'][$key]) && $target['settings'][$key] == $value) {
                continue;
              }
              else {
                break 2;
              }
            }
            $mappings[] = array('source' => $source_id, 'target' => $target_id);
            unset($targets[$target_id]);
            unset($sources[$source_id]);
            unset($suggested[$source_id]);
          }
        }
      }
    }

    return $mappings;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    parent::delete();

    $this->reschedule($this->id());
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
    if (!isset($this->targetPlugins[$delta])) {
      $targets = $this->getMappingTargets();
      $target = $this->mappings[$delta]['target'];

      // The target is a plugin.
      if (isset($targets[$target]['id'])) {
        $id = $targets[$target]['id'];
        $settings = $targets[$target];
        $settings['importer'] = $this;

        if (isset($this->mappings[$delta]['settings'])) {
          $settings['configuration'] = $this->mappings[$delta]['settings'];
        }
        $this->targetPlugins[$delta] = \Drupal::service('plugin.manager.feeds.target')->createInstance($id, $settings);
      }
      else {
        $this->targetPlugins[$delta] = FALSE;
      }
    }

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
      $options[$id] = check_plain($definition['title']);
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

    $this->pluginBags[$plugin_type] = new DefaultSinglePluginBag($manager, array($id), $configuration);
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
  public static function reschedule($importer_id = NULL) {
    // Get current reschedule list.
    $reschedule = \Drupal::state()->get('feeds.reschedule') ?: FALSE;

    // If Importer::reschedule(TRUE), or Importer::reschedule(FALSE) then set
    // the reschedule state to that. TRUE meaning all importers and FALSE
    // meaning none.
    if ($importer_id === TRUE || $importer_id === FALSE) {
      $reschedule = $importer_id;
    }
    // We are adding an importer to the reschedule list. Only add if all
    // importers weren't already flagged.
    elseif (is_string($importer_id) && $reschedule !== TRUE) {
      $reschedule = is_array($reschedule) ? $reschedule : array();
      $reschedule[$importer_id] = $importer_id;
    }

    \Drupal::state()->set('feeds.reschedule', $reschedule);

    return $reschedule;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    parent::preSave($storage_controller);

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
  public function getExportProperties() {
    $properties = parent::getExportProperties();
    $properties += $this->plugins;
    $properties['mappings'] = $this->mappings;
    return $properties;
  }

}
