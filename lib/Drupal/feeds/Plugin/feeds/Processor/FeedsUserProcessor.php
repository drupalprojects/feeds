<?php

/**
 * @file
 * FeedsUserProcessor class.
 */

namespace Drupal\feeds\Plugin\feeds\Processor;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\feeds\Plugin\ProcessorBase;
use Drupal\feeds\Plugin\Core\Entity\Feed;
use Drupal\feeds\FeedsParserResult;
use Drupal\feeds\FeedsValidationException;

/**
 * Defines a user processor.
 *
 * Creates Users from feed items.
 *
 * @Plugin(
 *   id = "user",
 *   title = @Translation("User processor"),
 *   description = @Translation("Creates users from feed items.")
 * )
 */
class FeedsUserProcessor extends ProcessorBase {
  /**
   * Define entity type.
   */
  public function entityType() {
    return 'user';
  }

  /**
   * Implements parent::entityInfo().
   */
  protected function entityInfo() {
    $info = parent::entityInfo();
    $info['label plural'] = t('Users');
    return $info;
  }

  /**
   * Creates a new user account in memory and returns it.
   */
  protected function newEntity(Feed $feed) {
    return entity_create('user', array(
      'uid' => 0,
      'roles' => array_filter(array_values($this->config['roles'])),
      'status' => $this->config['status'],
    ))->getBCEntity();
  }

  /**
   * Loads an existing user.
   */
  protected function entityLoad(Feed $feed, $uid) {
    $user = parent::entityLoad($feed, $uid)->getBCEntity();

    // Copy the password so that we can compare it again at save.
    $user->feeds_original_pass = $user->pass;
    return $user;
  }

  /**
   * Validates a user account.
   */
  protected function entityValidate($account) {
    if (empty($account->name) || empty($account->mail) || !valid_email_address($account->mail)) {
      throw new FeedsValidationException(t('User name missing or email not valid.'));
    }
  }

  /**
   * Save a user account.
   */
  protected function entitySave($account) {
    if ($this->config['defuse_mail']) {
      $account->mail = $account->mail . '_test';
    }

    // Remove pass from $account if the password is unchanged.
    if (isset($account->feeds_original_pass) && $account->pass == $account->feeds_original_pass) {
      unset($account->pass);
    }

    $account->save();
  }

  /**
   * Override parent::configDefaults().
   */
  public function configDefaults() {
    return array(
      'roles' => array(),
      'status' => 1,
      'defuse_mail' => FALSE,
    ) + parent::configDefaults();
  }

  /**
   * Override parent::configForm().
   */
  public function configForm(&$form_state) {
    $form = parent::configForm($form_state);
    $form['status'] = array(
      '#type' => 'radios',
      '#title' => t('Status'),
      '#description' => t('Select whether users should be imported active or blocked.'),
      '#options' => array(0 => t('Blocked'), 1 => t('Active')),
      '#default_value' => $this->config['status'],
    );

    $roles = user_roles(TRUE);
    unset($roles['authenticated']);
    $options = array();
    foreach ($roles as $role) {
      $options[$role->id()] = $role->label();
    }
    if ($options) {
      $form['roles'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Additional roles'),
        '#description' => t('Every user is assigned the "authenticated user" role. Select additional roles here.'),
        '#default_value' => $this->config['roles'],
        '#options' => $options,
      );
    }
    $form['defuse_mail'] = array(
      '#type' => 'checkbox',
      '#title' => t('Defuse e-mail addresses'),
      '#description' => t('This appends _test to all imported e-mail addresses to ensure they cannot be used as recipients.'),
      '#default_value' => $this->config['defuse_mail'],
    );
    return $form;
  }

  /**
   * Override setTargetElement to operate on a target item that is a node.
   */
  public function setTargetElement(Feed $feed, $target_user, $target_element, $value) {
    switch ($target_element) {
      case 'created':
        $target_user->created = feeds_to_unixtime($value, REQUEST_TIME);
        break;

      case 'language':
        $target_user->language = strtolower($value);
        break;

      default:
        parent::setTargetElement($feed, $target_user, $target_element, $value);
        break;
    }
  }

  /**
   * Return available mapping targets.
   */
  public function getMappingTargets() {
    $targets = parent::getMappingTargets();
    $targets += array(
      'name' => array(
        'name' => t('User name'),
        'description' => t('Name of the user.'),
        'optional_unique' => TRUE,
       ),
      'mail' => array(
        'name' => t('Email address'),
        'description' => t('Email address of the user.'),
        'optional_unique' => TRUE,
       ),
      'created' => array(
        'name' => t('Created date'),
        'description' => t('The created (e. g. joined) data of the user.'),
       ),
      'pass' => array(
        'name' => t('Unencrypted Password'),
        'description' => t('The unencrypted user password.'),
      ),
      'status' => array(
        'name' => t('Account status'),
        'description' => t('Whether a user is active or not. 1 stands for active, 0 for blocked.'),
      ),
      'language' => array(
        'name' => t('User language'),
        'description' => t('Default language for the user.'),
      ),
    );
    if (module_exists('openid')) {
      $targets['openid'] = array(
        'name' => t('OpenID identifier'),
        'description' => t('The OpenID identifier of the user. <strong>CAUTION:</strong> Use only for migration purposes, misconfiguration of the OpenID identifier can lead to severe security breaches like users gaining access to accounts other than their own.'),
        'optional_unique' => TRUE,
       );
    }

    // Let other modules expose mapping targets.
    $definitions = \Drupal::service('plugin.manager.feeds.mapper')->getDefinitions();
    foreach ($definitions as $definition) {
      $mapper = \Drupal::service('plugin.manager.feeds.mapper')->createInstance($definition['id']);
      $mapper->targets($targets, $this->entityType(), $this->bundle());
    }

    return $targets;
  }

  /**
   * Get id of an existing feed item term if available.
   */
  protected function existingEntityId(Feed $feed, FeedsParserResult $result) {
    if ($uid = parent::existingEntityId($feed, $result)) {
      return $uid;
    }

    // Iterate through all unique targets and try to find a user for the
    // target's value.
    foreach ($this->uniqueTargets($feed, $result) as $target => $value) {
      switch ($target) {
        case 'name':
          $uid = db_query("SELECT uid FROM {users} WHERE name = :name", array(':name' => $value))->fetchField();
          break;

        case 'mail':
          $uid = db_query("SELECT uid FROM {users} WHERE mail = :mail", array(':mail' => $value))->fetchField();
          break;
      }
      if ($uid) {
        // Return with the first nid found.
        return $uid;
      }
    }
    return 0;
  }
}
