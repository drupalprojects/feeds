<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\views\field\Link.
 */

namespace Drupal\feeds\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to the feed.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("feeds_feed_link")
 */
class Link extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['text'] = ['default' => ''];
    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text to display'),
      '#default_value' => $this->options['text'],
    ];
    parent::buildOptionsForm($form, $form_state);

    // The path is set by renderLink function so don't allow to set it.
    $form['alter']['path'] = ['#access' => FALSE];
    $form['alter']['external'] = ['#access' => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    if ($entity = $this->getEntity($values)) {
      return $this->renderLink($entity, $values);
    }
  }

  /**
   * Prepares the link to the feed.
   */
  protected function renderLink($feed, ResultRow $values) {
    if ($feed->access('view')) {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = $feed->getSystemPath('canonical');
      return !empty($this->options['text']) ? $this->options['text'] : $this->t('View');
    }
  }

}
