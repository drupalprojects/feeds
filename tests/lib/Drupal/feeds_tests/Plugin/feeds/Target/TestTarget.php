<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Target\TestTarget.
 */

namespace Drupal\feeds_tests\Plugin\feeds\Target;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\TargetBase;

/**
 * Defines a test_mapper field mapper.
 *
 * @Plugin(
 *   id = "test_mapper",
 *   title = @Translation("TestTarget")
 * )
 */
class TestTarget extends TargetBase {

  /**
   * {@inheritdoc}
   */
  public function targets(array &$targets) {
    $targets['test_target'] = array(
      'name' => t('Test Target'),
      'description' => t('This is a test target.'),
      'callback' => array($this, 'setTarget'),
      'summary_callback' => array($this, 'summary'),
      'form_callback' => array($this, 'form'),
    );
  }

  /**
   * {@inheritdoc}
   */
  function setTarget(FeedInterface $feed, EntityInterface $entity, $field_name, $value, array $mapping) {
    $entity->body['und'][0]['value'] = serialize($mapping);
  }

  /**
   * {@inheritdoc}
   */
  public function summary($mapping, $target, $form, $form_state) {
    $options = array(
      'option1' => t('Option 1'),
      'option2' => t('Another Option'),
      'option3' => t('Option for select'),
      'option4' => t('Another One'),
    );

    $items = array();
    if (!empty($mapping['checkbox']) && $mapping['checkbox']) {
      $items[] = t('Checkbox active.');
    }
    else {
      $items[] = t('Checkbox inactive.');
    }
    if (!empty($mapping['textfield'])) {
      $items[] = t('<strong>Textfield value</strong>: %textfield', array('%textfield' => $mapping['textfield']));
    }
    if (!empty($mapping['textarea'])) {
      $items[] = t('<strong>Textarea value</strong>: %textarea', array('%textarea' => $mapping['textarea']));
    }
    if (!empty($mapping['radios'])) {
      $items[] = t('<strong>Radios value</strong>: %radios', array('%radios' => $options[$mapping['radios']]));
    }
    if (!empty($mapping['select'])) {
      $items[] = t('<strong>Select value</strong>: %select', array('%select' => $options[$mapping['select']]));
    }
    $list = array(
      '#type' => 'ul',
      '#theme' => 'item_list',
      '#items' => $items,
    );
    return drupal_render($list);
  }

  /**
   * {@inheritdoc}
   */
  public function form($mapping, $target, $form, $form_state) {
    $mapping += array(
      'checkbox' => FALSE,
      'textfield' => '',
      'textarea' => '',
      'radios' => NULL,
      'select' => NULL,
    );
    return array(
      'checkbox' => array(
        '#type' => 'checkbox',
        '#title' => t('A checkbox'),
        '#default_value' => !empty($mapping['checkbox']),
      ),
      'textfield' => array(
        '#type' => 'textfield',
        '#title' => t('A text field'),
        '#default_value' => $mapping['textfield'],
        '#required' => TRUE,
      ),
      'textarea' => array(
        '#type' => 'textarea',
        '#title' => t('A textarea'),
        '#default_value' => $mapping['textarea'],
      ),
      'radios' => array(
        '#type' => 'radios',
        '#title' => t('Some radios'),
        '#options' => array('option1' => t('Option 1'), 'option2' => t('Another Option')),
        '#default_value' => $mapping['radios'],
      ),
      'select' => array(
        '#type' => 'select',
        '#title' => t('A select list'),
        '#options' => array('option3' => t('Option for select'), 'option4' => t('Another One')),
        '#default_value' => $mapping['select'],
      ),
    );
  }

}
