<?php

/**
 * @file
 * Contains \Drupal\feeds\Entity\Importer.
 */

namespace Drupal\feeds\Entity;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\EntityType;
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
   * The enabled status.
   *
   * @var bool
   */
  public $status = TRUE;

  // Every feed has a fetcher, a parser and a processor.
  public $fetcher = array(
    'plugin_key' => 'http',
    'config' => array(),
  );

  public $parser = array(
    'plugin_key' => 'syndication',
    'config' => array(),
  );

  public $processor = array(
    'plugin_key' => 'entity:node',
    'config' => array(),
  );

  public $mappings = array();

  // This array defines the variable names of the plugins above.
  protected $pluginTypes = array('fetcher', 'parser', 'processor');
  public $import_period = 1800;
  public $expire_period = 3600;
  public $import_on_create = TRUE;
  public $process_in_background = FALSE;

  protected $plugins = array();

  /**
   * Constructs a new Importer object.
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);

    // Instantiate fetcher, parser and processor, set their configuration if
    // stored info is available.

    foreach ($this->getPluginTypes() as $type) {
      $plugin_key = $this->{$type}['plugin_key'];

      $config = array();
      if (isset($this->{$type}['config'])) {
        $config = $this->{$type}['config'];
      }

      $this->setPlugin($type, $plugin_key, $config);
    }
  }

  /**
   * Report how many items *should* be created on one page load by this
   * importer.
   *
   * Note:
   *
   * It depends on whether parser implements batching if this limit is actually
   * respected. Further, if no limit is reported it doesn't mean that the
   * number of items that can be created on one page load is actually without
   * limit.
   *
   * @return
   *   A positive number defining the number of items that can be created on
   *   one page load. 0 if this number is unlimited.
   */
  public function getLimit() {
    return $this->processor->getLimit();
  }

  /**
   * Deletes configuration.
   *
   * Removes configuration information from database, does not delete
   * configuration itself.
   */
  public function delete() {
    parent::delete();

    $this->reschedule($this->id());
  }

  public function getPluginTypes() {
    return $this->pluginTypes;
  }

  public function getPlugins() {
    $plugins = array();
    foreach ($this->pluginTypes as $type) {
      $plugins[$type] = $this->plugins[$type];
    }

    return $plugins;
  }

  public function getFetcher() {
    return $this->plugins['fetcher'];
  }

  public function getParser() {
    return $this->plugins['parser'];
  }

  public function getProcessor() {
    return $this->plugins['processor'];
  }

  public function getPlugin($type) {
    return $this->plugins[$type];
  }

  /**
   * Set plugin.
   *
   * @param string $plugin_type
   *   The type of plugin. Either fetcher, parser, or processor.
   * @param $plugin_key
   *   A id key.
   */
  public function setPlugin($plugin_type, $plugin_key, array $config = array()) {
    $config['importer'] = $this;
    $plugin = \Drupal::service('plugin.manager.feeds.' . $plugin_type)->createInstance($plugin_key, $config);
    // Unset existing plugin, switch to new plugin.
    unset($this->plugins[$plugin_type]);
    $this->plugins[$plugin_type] = $plugin;
    // Set configuration information, blow away any previous information on
    // this spot.
    $this->$plugin_type = array(
      'plugin_key' => $plugin_key,
      'config' => $plugin->getConfiguration(),
    );

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
   * Reschedules one or all importers.
   *
   * @param string $importer_id
   *   If true, all importers will be rescheduled, if FALSE, no importers will
   *   be rescheduled, if an importer id, only importer of that id will be
   *   rescheduled.
   *
   * @return bool|array
   *   Returns true if all importers need rescheduling, or false if no
   *   rescheduling is required. An array of importers that need rescheduling.
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

}
