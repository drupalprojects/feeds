<?php

/**
 * @file
 * Contains \Drupal\feeds\Entity\Subscription.
 */

namespace Drupal\feeds\Entity;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\feeds\SubscriptionInterface;

/**
 * Defines the subscription entity class.
 *
 * @ContentEntityType(
 *   id = "feeds_subscription",
 *   label = @Translation("Subscription"),
 *   module = "feeds",
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage"
 *   },
 *   base_table = "feeds_subscription",
 *   entity_keys = {
 *     "id" = "fid",
 *     "label" = "label"
 *   },
 * )
 */
class Subscription extends ContentEntityBase implements SubscriptionInterface {

  protected $topicChanged = FALSE;

  // public function getFeedId() {
  //   return $this->get('fid')->value;
  // }

  public function getSecret() {
    return $this->get('secret')->value;
  }

  public function getTopic() {
    return $this->get('topic')->value;
  }

  public function setTopic($topic) {
    $topic = trim($topic);
    $this->topicChanged = $topic !== $this->getTopic();

    $this->set('topic', $topic);
  }

  public function getHub() {
    return $this->get('hub')->value;
  }

  public function setHub($hub) {
    $this->set('hub', $hub);
  }

  public function getState() {
    return $this->get('state')->value;
  }

  public function setState($state) {
    $this->set('state', $state);
  }

  public function getLease() {
    return (int) $this->get('lease')->value;
  }

  public function setLease($lease) {
    $this->set('lease', (int) $lease);
  }

  public function getExpire() {
    return (int) $this->get('expires')->value;
  }

  public function setExpire($expiration_time) {
    $this->set('expires', (int) $expiration_time);
  }

  public function checkSignature($sha1, $data) {
    return $sha1 === hash_hmac('sha1', $data, $this->getSecret());
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage_controller, $update = TRUE) {
    if (!$this->getSecret()) {
      $this->set('secret', substr(Crypt::randomBytesBase64(55), 0, 43));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage_controller, $update = TRUE) {
    if (!$this->getHub()) {
      return;
    }

    switch ($this->getState()) {
      // Don't do anything if we are in the process of subscribing.
      case 'subscribing':
      case 'subscribed':
        break;

      default:
        $this->queue('feeds_push_subscribe')->createItem($this->getFeedId());
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage_controller, array $subscriptions) {
    $queue = \Drupal::queue('feeds_push_unsubscribe');

    foreach ($subscriptions as $subscription) {

      switch ($subscription->getState()) {
        case 'subscribing':
        case 'subscribed':
          $queue->createItem($subscription);
          break;
      }
    }
  }

  protected function queue($name, $reliable = FALSE) {
    return \Drupal::queue($name, $reliable);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = array();

    $fields['fid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Feed ID'))
      ->setDescription(t('The feed ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setDescription(t('The label of the subscription.'))
      ->setRequired(TRUE)
      ->setDefaultValue('')
      ->setSetting('max_length', 255);

    $fields['topic'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Topic'))
      ->setDescription(t('The fully-qualified URL of the feed.'))
      ->setReadOnly(TRUE)
      ->setRequired(TRUE);

    $fields['hub'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Hub'))
      ->setDescription(t('The fully-qualified URL of the PuSH hub.'))
      ->setDefaultValue('');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the subscription was created.'));

    $fields['imported'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last import'))
      ->setDescription(t('The time that the feed was imported.'))
      ->setDefaultValue(0);

    $fields['lease'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Lease time'))
      ->setDescription(t('The time, in seconds of the lease.'));

    $fields['expires'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Expires'))
      ->setDescription(t('The UNIX timestamp when the subscription expires.'));

    $fields['secret'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Secret'))
      ->setDescription(t('The secret used to verify a request.'))
      ->setReadOnly(TRUE)
      ->setSetting('max_length', 43);

    $fields['state'] = BaseFieldDefinition::create('string')
      ->setLabel(t('State'))
      ->setDescription(t('The state of the subscription.'))
      ->setSetting('max_length', 64)
      ->setDefaultValue('unsubscribed');

    return $fields;
  }

}
