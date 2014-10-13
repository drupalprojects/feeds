<?php

/**
 * @file
 * Contains \Drupal\feeds\SubscriptionInterface.
 */

namespace Drupal\feeds;

use Drupal\Core\Entity\ContentEntityInterface;

interface SubscriptionInterface extends ContentEntityInterface {

  public function getTopic();

  public function getSecret();

  public function setTopic($topic);

  public function getHub();

  public function setHub($hub);

  public function getState();

  public function setState($state);

  public function getLease();

  public function setLease($lease);

  public function setExpire($expiration_time);

  public function getExpire();

  public function checkSignature($sha1, $data);

}
