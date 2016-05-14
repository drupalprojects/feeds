<?php

/**
 * @file
 * Contains FeedsUserProcessor.
 */

/**
 * Option to block users not found in the feed.
 *
 * @var string
 */
define('FEEDS_BLOCK_NON_EXISTENT', 'block');

/**
 * Feeds processor plugin. Create users from feed items.
 */
class FeedsUserProcessor extends FeedsProcessor {
  /**
   * Search by role name.
   */
  const ROLE_SEARCH_NAME = 'name';

  /**
   * Search by role id.
   */
  const ROLE_SEARCH_RID = 'rid';

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
  protected function newEntity(FeedsSource $source) {
    $account = parent::newEntity($source);
    $account->uid = 0;
    $account->roles = array_filter($this->config['roles']);
    $account->status = $this->config['status'];

    return $account;
  }

  /**
   * Loads an existing user.
   */
  protected function entityLoad(FeedsSource $source, $uid) {
    $user = parent::entityLoad($source, $uid);

    // Copy the password so that we can compare it again at save.
    $user->feeds_original_pass = $user->pass;
    return $user;
  }

  /**
   * Validates a user account.
   */
  protected function entityValidate($account) {
    parent::entityValidate($account);

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

    $edit = (array) $account;

    // Remove pass from $edit if the password is unchanged.
    if (isset($account->feeds_original_pass) && $account->pass == $account->feeds_original_pass) {
      unset($edit['pass']);
    }

    user_save($account, $edit);
    if ($account->uid && !empty($account->openid)) {
      $authmap = array(
        'uid' => $account->uid,
        'module' => 'openid',
        'authname' => $account->openid,
      );
      if (SAVED_UPDATED != drupal_write_record('authmap', $authmap, array('uid', 'module'))) {
        drupal_write_record('authmap', $authmap);
      }
    }
  }

  /**
   * Delete multiple user accounts.
   */
  protected function entityDeleteMultiple($uids) {
    user_delete_multiple($uids);
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
    unset($roles[2]);
    if (count($roles)) {
      $form['roles'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Additional roles'),
        '#description' => t('Every user is assigned the "authenticated user" role. Select additional roles here.'),
        '#default_value' => $this->config['roles'],
        '#options' => $roles,
      );
    }
    $form['defuse_mail'] = array(
      '#type' => 'checkbox',
      '#title' => t('Defuse e-mail addresses'),
      '#description' => t('This appends _test to all imported e-mail addresses to ensure they cannot be used as recipients.'),
      '#default_value' => $this->config['defuse_mail'],
    );
    $form['update_non_existent']['#options'][FEEDS_BLOCK_NON_EXISTENT] = t('Block non-existent users');
    return $form;
  }

  /**
   * Overrides FeedsProcessor::map().
   *
   * Ensures that the user is assigned additional roles that are configured on
   * the settings. The roles could have been revoked when there was mapped to
   * the "roles_list" target.
   */
  protected function map(FeedsSource $source, FeedsParserResult $result, $target_item = NULL) {
    $target_item = parent::map($source, $result, $target_item);

    // Assign additional roles as configured.
    $roles = array_filter($this->config['roles']);
    foreach ($roles as $rid) {
      $target_item->roles[$rid] = $rid;
    }

    return $target_item;
  }

  /**
   * Override setTargetElement to operate on a target item that is a node.
   */
  public function setTargetElement(FeedsSource $source, $target_user, $target_element, $value, array $mapping = array()) {
    switch ($target_element) {
      case 'created':
        $target_user->created = feeds_to_unixtime($value, REQUEST_TIME);
        break;

      case 'language':
        $target_user->language = strtolower($value);
        break;

      case 'roles_list':
        // Ensure that the role list is an array.
        $value = (array) $value;
        $this->rolesListSetTarget($source, $target_user, $target_element, $value, $mapping);
        break;

      default:
        parent::setTargetElement($source, $target_user, $target_element, $value);
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
      'roles_list' => array(
        'name' => t('User roles'),
        'description' => t('User roles provided as role names in comma separated list.'),
        'summary_callbacks' => array(array($this, 'rolesListSummaryCallback')),
        'form_callbacks' => array(array($this, 'rolesListFormCallback')),
      ),
    );
    if (module_exists('openid')) {
      $targets['openid'] = array(
        'name' => t('OpenID identifier'),
        'description' => t('The OpenID identifier of the user. <strong>CAUTION:</strong> Use only for migration purposes, misconfiguration of the OpenID identifier can lead to severe security breaches like users gaining access to accounts other than their own.'),
        'optional_unique' => TRUE,
       );
    }

    $this->getHookTargets($targets);

    return $targets;
  }

  /**
   * Get id of an existing feed item term if available.
   */
  protected function existingEntityId(FeedsSource $source, FeedsParserResult $result) {
    if ($uid = parent::existingEntityId($source, $result)) {
      return $uid;
    }

    // Iterate through all unique targets and try to find a user for the
    // target's value.
    foreach ($this->uniqueTargets($source, $result) as $target => $value) {
      switch ($target) {
        case 'name':
          $uid = db_query("SELECT uid FROM {users} WHERE name = :name", array(':name' => $value))->fetchField();
          break;
        case 'mail':
          $uid = db_query("SELECT uid FROM {users} WHERE mail = :mail", array(':mail' => $value))->fetchField();
          break;
        case 'openid':
          $uid = db_query("SELECT uid FROM {authmap} WHERE authname = :authname AND module = 'openid'", array(':authname' => $value))->fetchField();
          break;
      }
      if ($uid) {
        // Return with the first nid found.
        return $uid;
      }
    }
    return 0;
  }

  /**
   * Overrides FeedsProcessor::clean().
   *
   * Block users instead of deleting them.
   *
   * @param FeedsState $state
   *   The FeedsState object for the given stage.
   */
  protected function clean(FeedsState $state) {
    // Delegate to parent if not blocking or option not set.
    if (!isset($this->config['update_non_existent']) || $this->config['update_non_existent'] !== FEEDS_BLOCK_NON_EXISTENT) {
      return parent::clean($state);
    }

    if (!empty($state->removeList)) {
      // @see user_user_operations_block().
      // The following foreach is copied from above function but with an added
      // counter to count blocked users.
      foreach (user_load_multiple($state->removeList) as $account) {
        $this->loadItemInfo($account);
        $account->feeds_item->hash = $this->config['update_non_existent'];
        // For efficiency manually save the original account before applying any
        // changes.
        $account->original = clone $account;
        user_save($account, array('status' => 0));
        $state->blocked++;
      }
    }
  }

  /**
   * Returns default values for mapper "roles_list".
   */
  public function rolesListDefaults() {
    $roles = user_roles(TRUE);
    unset($roles[DRUPAL_AUTHENTICATED_RID]);
    $rids = array_keys($roles);
    $rids = array_combine($rids, $rids);
    return array(
      'role_search' => self::ROLE_SEARCH_NAME,
      'allowed_roles' => $rids,
      'autocreate' => 0,
      'revoke_roles' => 1,
    );
  }

  /**
   * Mapping configuration summary callback for target "roles_list".
   */
  public function rolesListSummaryCallback(array $mapping, $target, array $form, array $form_state) {
    $options = array();

    // Add in defaults.
    $defaults = $this->rolesListDefaults();
    $mapping += $defaults;
    $mapping['allowed_roles'] += $defaults['allowed_roles'];

    // Role search.
    $role_search_options = $this->rolesListRoleSearchOptions();
    $options[] = t('Search roles by: <strong>@search</strong>', array('@search' => $role_search_options[$mapping['role_search']]));

    // Allowed roles.
    $role_names = array();
    $roles = user_roles(TRUE);
    foreach (array_filter($mapping['allowed_roles']) as $rid => $enabled) {
      $role_names[] = $roles[$rid];
    }

    if (empty($role_names)) {
      $role_names = array('<' . t('none') . '>');
    }
    $options[] = t('Allowed roles: %roles', array('%roles' => implode(', ', $role_names)));

    // Autocreate.
    if ($mapping['autocreate']) {
      $options[] = t('Automatically create roles');
    }
    else {
      $options[] = t('Only assign existing roles');
    }

    // Revoke roles.
    if ($mapping['revoke_roles']) {
      $options[] = t('Revoke roles: yes');
    }
    else {
      $options[] = t('Revoke roles: no');
    }

    return implode('<br />', $options);
  }

  /**
   * Mapping configuration form callback for target "roles_list".
   */
  public function rolesListFormCallback(array $mapping, $target, array $form, array $form_state) {
    // Add in defaults.
    $defaults = $this->rolesListDefaults();
    $mapping += $defaults;
    $mapping['allowed_roles'] += $defaults['allowed_roles'];

    $allowed_roles_options = user_roles(TRUE);
    unset($allowed_roles_options[DRUPAL_AUTHENTICATED_RID]);

    return array(
      'role_search' => array(
        '#type' => 'select',
        '#title' => t('Search roles by'),
        '#options' => $this->rolesListRoleSearchOptions(),
        '#default_value' => $mapping['role_search'],
      ),
      'allowed_roles' => array(
        '#type' => 'checkboxes',
        '#title' => t('Allowed roles'),
        '#options' => $allowed_roles_options,
        '#default_value' => $mapping['allowed_roles'],
        '#description' => t('Select the roles to accept from the feed.<br />Any other roles will be ignored.')
      ),
      'autocreate' => array(
        '#type' => 'checkbox',
        '#title' => t('Auto create'),
        '#description' => t("Create the role if it doesn't exist."),
        '#default_value' => $mapping['autocreate'],
      ),
      'revoke_roles' => array(
        '#type' => 'checkbox',
        '#title' => t('Revoke roles'),
        '#description' => t('If enabled, roles that are not provided by the feed will be revoked for the user. This affects only the "Allowed roles" as configured above.'),
        '#default_value' => $mapping['revoke_roles'],
      ),
    );
  }

  /**
   * Returns role list options.
   */
  public function rolesListRoleSearchOptions() {
    return array(
      self::ROLE_SEARCH_NAME => t('Role name'),
      self::ROLE_SEARCH_RID => t('Role ID'),
    );
  }

  /**
   * Sets role target on the user entity.
   */
  public function rolesListSetTarget(FeedsSource $source, $entity, $target, array $values, array $mapping) {
    // Add in defaults.
    $defaults = $this->rolesListDefaults();
    $mapping += $defaults;
    $mapping['allowed_roles'] += $defaults['allowed_roles'];

    // Eventually revoke roles. Do not touch roles that are not allowed to set
    // by the source.
    if ($mapping['revoke_roles']) {
      foreach ($mapping['allowed_roles'] as $rid) {
        unset($entity->roles[$rid]);
      }
    }

    foreach ($values as $value) {
      $role = NULL;

      $value = trim($value);
      if (strlen($value) < 1) {
        // No role provided. Continue to the next role.
        continue;
      }

      switch ($mapping['role_search']) {
        case self::ROLE_SEARCH_NAME:
          $role = user_role_load_by_name($value);
          if (!$role && !empty($mapping['autocreate'])) {
            // Create new role if role doesn't exist.
            $role = new stdClass();
            $role->name = $value;
            user_role_save($role);
            $role = user_role_load_by_name($role->name);
          }
          break;

        case self::ROLE_SEARCH_RID:
          $role = user_role_load($value);
          break;
      }

      if ($role) {
        // Check if the role may be assigned.
        if (isset($mapping['allowed_roles'][$role->rid]) && !$mapping['allowed_roles'][$role->rid]) {
          // This role may *not* be assiged.
          continue;
        }
        $entity->roles[$role->rid] = $role->name;
      }
    }
  }
}
