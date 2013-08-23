<?php

/**
 * @file
 * Contains \Drupal\feeds\Entity\Importer.
 */

namespace Drupal\feeds\Entity;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\DefaultSinglePluginBag;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\feeds\ImporterInterface;

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
  protected $pluginTypes = array('fetcher', 'parser', 'processor');

  /**
   * Plugin ids and configuration.
   *
   * @todo Move this to sotrage controller.
   */
  protected $plugins = array(
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
   * The plugin bags that store feeds plugins keyed by plugin type.
   *
   * @var \Drupal\Component\Plugin\DefaultSinglePluginBag[]
   */
  protected $pluginBags = array();

  public $import_period = 1800;
  public $expire_period = 3600;
  public $import_on_create = TRUE;
  public $process_in_background = FALSE;

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

    // Setup plugins.
    foreach ($this->getPluginTypes() as $type) {
      $id = $this->plugins[$type]['id'];

      $configuration = array('importer' => $this);
      if (isset($this->plugins[$type]['configuration'])) {
        $configuration += $this->plugins[$type]['configuration'];
      }
      $manager = \Drupal::service("plugin.manager.feeds.$type");

      $this->pluginBags[$type] = new DefaultSinglePluginBag($manager, array($id), $configuration);
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
  public function getMappings() {
    return $this->getProcessor()->getMappings();
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
   */
  public function setPlugin($plugin_type, $plugin_id) {
    $this->plugins[$plugin_type]['id'] = $plugin_id;
    $this->pluginBags[$plugin_type]->addInstanceID($plugin_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getExportProperties() {
    $properties = parent::getExportProperties();
    $properties += $this->plugins;
    return $properties;
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
    $reschedule = \Drupal::state()->get('feeds.reschedule') ? : FALSE;

    if ($importer_id === TRUE || $importer_id === FALSE) {
      $reschedule = $importer_id;
    }
    elseif (is_string($importer_id) && $reschedule !== TRUE) {
      $reschedule = is_array($reschedule) ? $reschedule : array();
      $reschedule[$importer_id] = $importer_id;
    }

    \Drupal::state()->set('feeds.reschedule', $reschedule);
    if ($reschedule === TRUE) {
      return entity_load_multiple('feeds_importer');
    }

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
  }

}
