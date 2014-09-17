<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\views\field\Link.
 */

namespace Drupal\feeds\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to the feed.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("feeds_feed_link")
 */
class Link extends FieldPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['text'] = array('default' => '', 'translatable' => TRUE);
    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    $form['text'] = array(
      '#type' => 'textfield',
      '#title' => t('Text to display'),
      '#default_value' => $this->options['text'],
    );
    parent::buildOptionsForm($form, $form_state);

    // The path is set by renderLink function so don't allow to set it.
    $form['alter']['path'] = array('#access' => FALSE);
    $form['alter']['external'] = array('#access' => FALSE);
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

  protected function renderLink($feed, ResultRow $values) {
    if ($feed->access('view')) {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = 'feed/' . $feed->id();
      $text = !empty($this->options['text']) ? $this->options['text'] : t('view');
      return $text;
    }
  }

}
