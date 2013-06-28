<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Handler\NodeHandler.
 */

namespace Drupal\feeds\Plugin\feeds\Handler;

use Drupal\Component\Annotation\Plugin;
use Drupal\Component\Plugin\PluginBase;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedsAccessException;
use Drupal\feeds\FeedsParserResult;

/**
 * Handles special node entity operations.
 *
 * @Plugin(
 *   id = "node"
 * )
 */
class NodeHandler extends PluginBase {

  protected $config;

  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->importer = $configuration['importer'];
    unset($configuration['importer']);
    $this->config = $configuration + $this->configDefaults();
  }

  public function getConfig() {
    return $this->config + $this->configDefaults();
  }

  public static function applies($processor) {
    return $processor->entityType() == 'node';
  }

  /**
   * Creates a new user account in memory and returns it.
   */
  public function newEntityValues(FeedInterface $feed, &$values) {
    $node_settings = entity_load('node_type', $this->importer->processor->bundle())->getModuleSettings('node');

    // Ensure default settings.
    $node_settings += array(
      'options' => array('status', 'promote'),
      'preview' => DRUPAL_OPTIONAL,
      'submitted' => TRUE,
    );

    $values['uid'] = $this->config['author'];
    $values['status'] = (int) in_array('status', $node_settings['options']);
    $values['log'] = 'Created by FeedsNodeProcessor';
    $values['promote'] = (int) in_array('promote', $node_settings['options']);
  }

  /**
   * Implements parent::entityInfo().
   */
  public function entityInfoAlter(array &$info) {
    $info['label_plural'] = t('Nodes');
    return $info;
  }

  /**
   * Override parent::configDefaults().
   */
  public function configDefaults() {
    $defaults = array();
    $defaults['author'] = 0;
    $defaults['authorize'] = TRUE;
    $defaults['expire'] = FEEDS_EXPIRE_NEVER;
    $defaults['status'] = 1;

    return $defaults;
  }

  public function formAlter(array &$form, array &$form_state) {
    $author = user_load($this->config['author']);
    $form['author'] = array(
      '#type' => 'textfield',
      '#title' => t('Author'),
      '#description' => t('Select the author of the nodes to be created - leave empty to assign "anonymous".'),
      '#autocomplete_path' => 'user/autocomplete',
      '#default_value' => empty($author->name) ?  'anonymous' : check_plain($author->name),
    );
    $form['authorize'] = array(
      '#type' => 'checkbox',
      '#title' => t('Authorize'),
      '#description' => t('Check that the author has permission to create the node.'),
      '#default_value' => $this->config['authorize'],
    );
    $period = drupal_map_assoc(array(FEEDS_EXPIRE_NEVER, 3600, 10800, 21600, 43200, 86400, 259200, 604800, 2592000, 2592000 * 3, 2592000 * 6, 31536000), array($this, 'formatExpire'));
    $form['expire'] = array(
      '#type' => 'select',
      '#title' => t('Expire nodes'),
      '#options' => $period,
      '#description' => t('Select after how much time nodes should be deleted. The node\'s published date will be used for determining the node\'s age, see Mapping settings.'),
      '#default_value' => $this->config['expire'],
    );
  }

  public function validateForm(array &$form, array &$form_state) {
    if ($author = user_load_by_name($form_state['values']['author'])) {
      $form_state['values']['author'] = $author->uid;
    }
    else {
      $form_state['values']['author'] = 0;
    }
  }

  public function submitForm(array &$form, array &$form_state) {
    if ($this->config['expire'] != $form_state['values']['expire']) {
      $this->importer->reschedule($this->importer->id());
    }
  }

  /**
   * Loads an existing user.
   */
  public function entityPrepare(FeedInterface $feed, $node) {
    $update_existing = $this->importer->processor->getConfig('update_existing');

    if ($update_existing != FEEDS_UPDATE_EXISTING) {
      $node->uid = $this->config['author'];
    }

    // node_object_prepare($node);

    // Workaround for issue #1247506. See #1245094 for backstory.
    if (!empty($node->menu)) {
      // If the node has a menu item(with a valid mlid) it must be flagged
      // 'enabled'.
      $node->menu['enabled'] = (int) (bool) $node->menu['mlid'];
    }

    // Populate properties that are set by node_object_prepare().
    if ($update_existing == FEEDS_UPDATE_EXISTING) {
      $node->log = 'Updated by FeedsNodeProcessor';
    }
    else {
      $node->log = 'Replaced by FeedsNodeProcessor';
    }
  }

  /**
   * Validates a user account.
   */
  public function entityValidate($node) {
  }

  /**
   * Check that the user has permission to save a node.
   */
  public function entitySaveAccess($entity) {
    // The check will be skipped for anonymous nodes.
    if ($this->config['authorize'] && !empty($entity->uid)) {

      $author = user_load($entity->uid);

      // If the uid was mapped directly, rather than by email or username, it
      // could be invalid.
      if (!$author) {
        $message = 'User %uid is not a valid user.';
        throw new FeedsAccessException(t($message, array('%uid' => $entity->uid)));
      }

      if ($entity->isNew()) {
        $op = 'create';
        $access = node_access($op, $entity->bundle(), $author);
      }
      else {
        $op = 'update';
        $access = node_access($op, $entity, $author);
      }

      if (!$access) {
        $message = 'User %name is not authorized to %op content type %content_type.';
        throw new FeedsAccessException(t($message, array('%name' => $author->name, '%op' => $op, '%content_type' => $entity->bundle())));
      }
    }
  }

  public function preSave($entity) {
    if (!isset($entity->uid) || !is_numeric($entity->uid)) {
       $entity->uid = $this->config['author'];
    }
    if (drupal_strlen($entity->title) > 255) {
      $entity->title = drupal_substr($entity->title, 0, 255);
    }
  }

  /**
   * Override setTargetElement to operate on a target item that is a node.
   */
  public function setTargetElement(FeedInterface $feed, $node, $target_element, $value) {
    switch ($target_element) {
      case 'user_name':
        if ($user = user_load_by_name($value)) {
          $node->uid = $user->uid;
        }
        break;

      case 'user_mail':
        if ($user = user_load_by_mail($value)) {
          $node->uid = $user->uid;
        }
        break;
    }
  }

  /**
   * Return available mapping targets.
   */
  public function getMappingTargets(array &$targets) {
    $targets['title']['optional_unique'] = TRUE;
    $targets['user_name'] = array(
      'name' => t('Username'),
      'description' => t('The Drupal username of the node author.'),
    );
    $targets['user_mail'] = array(
      'name' => t('User email'),
      'description' => t('The email address of the node author.'),
    );
  }

  /**
   * Overrides parent::expiryQuery().
   */
  public function expiryQuery(FeedInterface $feed, $select, $time) {
    $data_table = $select->join('node_field_data', 'nfd', 'e.nid = nfd.nid');
    $select->condition('nfd.created', REQUEST_TIME - $time, '<');
    return $select;
  }

  /**
   * Get nid of an existing feed item node if available.
   */
  public function existingEntityId(FeedInterface $feed, FeedsParserResult $result) {
    $nid = FALSE;
    // Iterate through all unique targets and test whether they do already
    // exist in the database.
    foreach ($this->importer->processor->uniqueTargets($feed, $result) as $target => $value) {

      switch ($target) {
        case 'nid':
          $nid = db_query("SELECT nid FROM {node} WHERE nid = :nid", array(':nid' => $value))->fetchField();
          break;

        case 'title':
          $nid = db_query("SELECT nid FROM {node_field_data} WHERE title = :title AND type = :type", array(':title' => $value, ':type' => $this->importer->processor->bundle()))->fetchField();
          break;
      }
      if ($nid) {
        // Return with the first nid found.
        return $nid;
      }
    }
    return 0;
  }

}
