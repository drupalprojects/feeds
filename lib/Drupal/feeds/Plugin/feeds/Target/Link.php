<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Target\Link.
 */

namespace Drupal\feeds\Plugin\feeds\Target;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\feeds\FeedsElement;
use Drupal\feeds\Plugin\FieldTargetBase;
use Drupal\field\Plugin\Core\Entity\FieldInstance;

/**
 * Defines a link field mapper.
 *
 * @Plugin(
 *   id = "link",
 *   title = @Translation("Link")
 * )
 */
class Link extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  protected $fieldTypes = array('link');

  /**
   * {@inheritdoc}
   */
  protected function applyTargets(FieldInstance $instance) {

    $targets = array();

    $targets[$instance->getFieldName()] = array(
      'name' => t('@name: URL', array('@name' => $instance->label())),
      'callback' => array($this, 'setTarget'),
      'description' => t('The @label field of the entity.', array('@label' => $instance->label())),
      'columns' => array('url'),
    );
    if ($instance->getFieldSetting('title')) {
      $targets[$instance->getFieldName()]['columns'][] = 'title';
    }

    return $targets;
  }

  /**
   * {@inheritdoc}
   */
  protected function defaults() {
    return array('title' => '', 'url' => '');
  }

}
