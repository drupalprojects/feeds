<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\views\field\Link.
 */

namespace Drupal\feeds\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
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

  use RedirectDestinationTrait;

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['text'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
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
   *
   * @param \Drupal\Core\Entity\EntityInterface $feed
   *   The feed entity this field belongs to.
   * @param ResultRow $values
   *   The values retrieved from the view's result set.
   *
   * @return string
   *   Returns a string for the link text.
   */
  protected function renderLink($feed, ResultRow $values) {
    if ($feed->access('view')) {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['url'] = $feed->urlInfo();
      $text = !empty($this->options['text']) ? $this->options['text'] : $this->t('View');
      return $text;
    }
  }

}
