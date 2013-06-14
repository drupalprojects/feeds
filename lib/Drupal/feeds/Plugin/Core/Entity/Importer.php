<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\Core\Entity\FeedType.
 */

namespace Drupal\feeds\Plugin\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\feeds\ImporterInterface;
use Drupal\feeds\Plugin\FeedsPlugin;

/**
 * Defines the feeds importer entity.
 *
 * @EntityType(
 *   id = "feeds_importer",
 *   label = @Translation("Feed importer"),
 *   module = "feeds",
 *   controllers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigStorageController",
 *     "access" = "Drupal\feeds\ImporterAccessController",
 *     "list" = "Drupal\Core\Config\Entity\ConfigEntityListController",
 *     "form" = {
 *       "default" = "Drupal\feeds\ImporterFormController"
 *     }
 *   },
 *   config_prefix = "feeds.importer",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid"
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
   * Name of the importer.
   *
   * @var string
   */
  public $name;

  /**
   * Description of the importer.
   *
   * @var string
   */
  public $description;

  /**
   * The disabled status.
   *
   * @var bool
   */
  public $disabled = FALSE;

  // Every feed has a fetcher, a parser and a processor.
  // These variable names match the possible return values of
  // FeedsPlugin::typeOf().
  public $fetcher, $parser, $processor;

  // This array defines the variable names of the plugins above.
  protected $plugin_types = array('fetcher', 'parser', 'processor');
  public $config = array();

  /**
   * Instantiate class variables, initialize and configure
   * plugins.
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);

    $this->config += $this->configDefaults();

    // Instantiate fetcher, parser and processor, set their configuration if
    // stored info is available.
    foreach ($this->plugin_types as $type) {
      $plugin = feeds_plugin($this->config[$type]['plugin_key'], $this->id());
      $plugin->importer = $this;

      if (isset($this->config[$type]['config'])) {
        $plugin->setConfig($this->config[$type]['config']);
      }
      $this->$type = $plugin;
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

    feeds_reschedule($this->id());
  }

  /**
   * Set plugin.
   *
   * @param $plugin_key
   *   A fetcher, parser or processor plugin.
   *
   * @todo Error handling, handle setting to the same plugin.
   */
  public function setPlugin($plugin_key) {
    // $plugin_type can be either 'fetcher', 'parser' or 'processor'
    if ($plugin_type = FeedsPlugin::typeOf($plugin_key)) {
      if ($plugin = feeds_plugin($plugin_key, $this->id())) {
        // Unset existing plugin, switch to new plugin.
        unset($this->$plugin_type);
        $this->$plugin_type = $plugin;
        // Set configuration information, blow away any previous information on
        // this spot.
        $this->config[$plugin_type] = array(
          'plugin_key' => $plugin_key,
          'config' => $plugin->getConfig(),
        );
      }
    }
  }

  /**
   * Similar to setConfig but adds to existing configuration.
   *
   * @param $config
   *   Array containing configuration information. Will be filtered by the keys
   *   returned by configDefaults().
   */
  public function addConfig($config) {
    $this->config = is_array($this->config) ? array_merge($this->config, $config) : $config;
    $default_keys = $this->configDefaults();
    $this->config = array_intersect_key($this->config, $default_keys);
  }

  /**
   * Get configuration of this feed.
   */
  public function getConfig() {
    foreach ($this->plugin_types as $type) {
      $this->config[$type]['config'] = $this->$type->getConfig();
    }

    return $this->config;
  }

  /**
   * Return defaults for feed configuration.
   */
  public function configDefaults() {
    return array(
      'fetcher' => array(
        'plugin_key' => 'http',
        'config' => array(),
      ),
      'parser' => array(
        'plugin_key' => 'syndication',
        'config' => array(),
      ),
      'processor' => array(
        'plugin_key' => 'node',
        'config' => array(),
      ),
      'content_type' => '',
      'update' => 0,
      'import_period' => 1800, // Refresh every 30 minutes by default.
      'expire_period' => 3600, // Expire every hour by default, this is a hidden setting.
      'import_on_create' => TRUE, // Import on submission.
      'process_in_background' => FALSE,
    );
  }

  /**
   * Override parent::configForm().
   */
  public function configForm(&$form_state) {
    $config = $this->getConfig();
    $form = array();
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#description' => t('A human readable name of this importer.'),
      '#default_value' => $this->name,
      '#required' => TRUE,
    );
    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => t('Description'),
      '#description' => t('A description of this importer.'),
      '#default_value' => $this->description,
    );
    $node_types = node_type_get_names();
    array_walk($node_types, 'check_plain');
    $form['content_type'] = array(
      '#type' => 'select',
      '#title' => t('Attach to content type'),
      '#description' => t('If "Use standalone form" is selected a source is imported by using a form under !import_form.
                           If a content type is selected a source is imported by creating a node of that content type.',
                           array('!import_form' => l(url('import', array('absolute' => TRUE)), 'import', array('attributes' => array('target' => '_new'))))),
      '#options' => array('' => t('Use standalone form')) + $node_types,
      '#default_value' => $config['content_type'],
    );
    $cron_required =  ' ' . l(t('Requires cron to be configured.'), 'http://drupal.org/cron', array('attributes' => array('target' => '_new')));
    $period = drupal_map_assoc(array(900, 1800, 3600, 10800, 21600, 43200, 86400, 259200, 604800, 2419200), 'format_interval');
    foreach ($period as &$p) {
      $p = t('Every !p', array('!p' => $p));
    }
    $period = array(
      FEEDS_SCHEDULE_NEVER => t('Off'),
      0 => t('As often as possible'),
    ) + $period;
    $form['import_period'] = array(
      '#type' => 'select',
      '#title' => t('Periodic import'),
      '#options' => $period,
      '#description' => t('Choose how often a source should be imported periodically.') . $cron_required,
      '#default_value' => $config['import_period'],
    );
    $form['import_on_create'] = array(
      '#type' => 'checkbox',
      '#title' => t('Import on submission'),
      '#description' => t('Check if import should be started at the moment a standalone form or node form is submitted.'),
      '#default_value' => $config['import_on_create'],
    );
    $form['process_in_background'] = array(
      '#type' => 'checkbox',
      '#title' => t('Process in background'),
      '#description' => t('For very large imports. If checked, import and delete tasks started from the web UI will be handled by a cron task in the background rather than by the browser. This does not affect periodic imports, they are handled by a cron task in any case.') . $cron_required,
      '#default_value' => $config['process_in_background'],
    );
    return $form;
  }

  public function configFormValidate(array &$values) {
    foreach ($this->plugin_types as $type) {
      if (isset($values[$type])) {
        $this->$type->configFormValidate($values[$type]);
      }
    }
  }

  /**
   * Reschedule if import period changes.
   */
  public function configFormSubmit(&$values) {
    if ($this->config['import_period'] != $values['import_period']) {
      feeds_reschedule($this->id());
    }
    $this->name = $values['name'];
    $this->description = $values['description'];
    $this->addConfig($values);

    $this->save();
    drupal_set_message(t('Your changes have been saved.'));
  }

  public function save() {
    $plugins = array();

    foreach ($this->plugin_types as $type) {
      $this->config[$type]['config'] = $this->$type->getConfig();
      $plugins[$type] = $this->$type;
      unset($this->$type);
    }

    parent::save();

    foreach ($plugins as $type => $plugin) {
      $this->$type = $plugin;
    }
  }

  public function getPluginTypes() {
    return $this->plugin_types;
  }
}
