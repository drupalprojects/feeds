<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\views\field\Feed.
 */

namespace Drupal\feeds\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Field handler to provide simple renderer that allows linking to a feed.
 * Definition terms:
 * - link_to_feed default: Should this field have the checkbox "link to feed" enabled by default.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("feeds_feed")
 */
class Feed extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    // Don't add the additional fields to groupby
    if (!empty($this->options['link_to_feed'])) {
      $this->additional_fields['fid'] = [
        'table' => 'feeds_feed',
        'field' => 'fid',
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_to_feed'] = [
      'default' => !empty($this->definition['link_to_feed default']),
    ];

    return $options;
  }

  /**
   * Provides link to feed option.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['link_to_feed'] = [
      '#title' => $this->t('Link this field to the original feed'),
      '#description' => $this->t("Enable to override this field's links."),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_feed']),
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * Prepares link to the feed.
   *
   * @param string $data
   *   The XSS safe string for the link text.
   * @param \Drupal\views\ResultRow $values
   *   The values retrieved from a single row of a view's query result.
   *
   * @return string
   *   Returns a string for the link text.
   */
  protected function renderLink($data, ResultRow $values) {
    $this->options['alter']['make_link'] = FALSE;

    if (empty($this->options['link_to_feed']) || empty($this->additional_fields['fid'])) {
      return $data;
    }

    if ($data === NULL || $data === '') {
      return $data;
    }

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['url'] = Url::fromRoute('entity.feeds_feed.canonical', ['feeds_feed' => $this->getValue($values, 'fid')]);

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return $this->renderLink($this->sanitizeValue($this->getValue($values)), $values);
  }

}
