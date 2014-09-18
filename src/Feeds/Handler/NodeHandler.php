<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Handler\NodeHandler.
 */

namespace Drupal\feeds\Feeds\Handler;

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\PluginBase;
use Drupal\feeds\Plugin\Type\Processor\ProcessorInterface;

/**
 * Handles special node entity operations.
 *
 * @Plugin(id = "node")
 */
class NodeHandler extends PluginBase {

  public static function applies($processor) {
    return $processor->entityType() == 'node';
  }

  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configuration += $this->defaultConfiguration();
  }

  /**
   * Creates a new user account in memory and returns it.
   */
  public function newEntityValues(FeedInterface $feed, $values) {
    $node_settings = entity_load('node_type', $this->importer->getProcessor()->bundle())->getModuleSettings('node');

    // Ensure default settings.
    $node_settings += array(
      'options' => array('status', 'promote'),
      'preview' => DRUPAL_OPTIONAL,
      'submitted' => TRUE,
    );

    $values['uid'] = $this->configuration['author'];
    $values['status'] = (int) in_array('status', $node_settings['options']);
    $values['log'] = 'Created by FeedsNodeProcessor';
    $values['promote'] = (int) in_array('promote', $node_settings['options']);

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $defaults = array();
    $defaults['author'] = 0;
    $defaults['status'] = 1;

    return $defaults;
  }

  public function buildConfigurationForm(array &$form, FormStateInterface $form_state) {
    $author = user_load($this->configuration['author']);
    $form['author'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Author'),
      '#description' => $this->t('Select the author of the nodes to be created. Leave blank for %anonymous.', array('%anonymous' => \Drupal::config('user.settings')->get('anonymous'))),
      '#autocomplete_route_name' => 'user.autocomplete',
      '#default_value' => String::checkPlain($author->getUsername()),
    );
  }

  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values =& $form_state->getValue(array('processor', 'configuration'));
    if ($author = user_load_by_name($values['author'])) {
      $values['author'] = $author->id();
    }
    else {
      $values['author'] = 0;
    }
  }

  /**
   * Loads an existing user.
   */
  public function entityPrepare(FeedInterface $feed, $node) {
    $update_existing = $this->importer->getProcessor()->getConfiguration('update_existing');
    if ($update_existing != ProcessorInterface::UPDATE_EXISTING) {
      $node->uid = $this->configuration['author'];
    }
    // Workaround for issue #1247506. See #1245094 for backstory.
    if (!empty($node->menu)) {
      // If the node has a menu item(with a valid mlid) it must be flagged
      // 'enabled'.
      $node->menu['enabled'] = (int) (bool) $node->menu['mlid'];
    }

    // Populate properties that are set by node_object_prepare().
    if ($update_existing == ProcessorInterface::UPDATE_EXISTING) {
      $node->log = 'Updated by FeedsNodeProcessor';
    }
    else {
      $node->log = 'Replaced by FeedsNodeProcessor';
    }
  }

}
