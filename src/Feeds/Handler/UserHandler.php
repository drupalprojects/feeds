<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Handler\UserHandler.
 */

namespace Drupal\feeds\Feeds\Handler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Exception\ValidationException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\PluginBase;

/**
 * Handles special user entity operations.
 *
 * @Plugin(id = "user")
 */
class UserHandler extends PluginBase {

  public static function applies($processor) {
    return $processor->entityType() === 'user';
  }

  /**
   * Creates a new user account in memory and returns it.
   */
  public function newEntityValues(FeedInterface $feed, &$values) {
    $values['uid'] = 0;
    $values['roles'] = array_filter(array_values($this->configuration['roles']));
    $values['status'] = $this->configuration['status'];

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function entityInfo(array &$info) {
    $info['label_plural'] = $this->t('Users');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $defaults = array();
    $defaults['roles'] = array();
    $defaults['status'] = 1;
    $defaults['defuse_mail'] = FALSE;

    return $defaults;
  }

  public function buildConfigurationForm(array &$form, FormStateInterface $form_state) {
    $form['status'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Status'),
      '#description' => $this->t('Select whether users should be imported active or blocked.'),
      '#options' => array(0 => $this->t('Blocked'), 1 => $this->t('Active')),
      '#default_value' => $this->configuration['status'],
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
        '#title' => $this->t('Additional roles'),
        '#description' => $this->t('Every user is assigned the "authenticated user" role. Select additional roles here.'),
        '#default_value' => $this->configuration['roles'],
        '#options' => $options,
      );
    }
    $form['defuse_mail'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Defuse e-mail addresses'),
      '#description' => $this->t('This appends _test to all imported e-mail addresses to ensure they cannot be used as recipients.'),
      '#default_value' => $this->configuration['defuse_mail'],
    );
  }

  /**
   * Loads an existing user.
   */
  public function entityPrepare(FeedInterface $feed, $user) {
    // Copy the password so that we can compare it again at save.
    $user->feeds_original_pass = $user->pass;
  }

  /**
   * Validates a user account.
   */
  public function entityValidate($account) {
    if (empty($account->name->value) || empty($account->mail->value) || !valid_email_address($account->mail->value)) {
      throw new ValidationException('User name missing or email not valid.');
    }

    if ($this->configuration['defuse_mail']) {
      $account->mail = $account->mail->value . '_test';
    }

    // Remove pass from $account if the password is unchanged.
    if (isset($account->feeds_original_pass) && $account->pass == $account->feeds_original_pass) {
      unset($account->pass);
    }
  }

}
