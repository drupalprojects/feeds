<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\views\field\Feed.
 */

namespace Drupal\feeds\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
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
      $this->additional_fields['fid'] = array('table' => 'feeds_feed', 'field' => 'fid');
      if (\Drupal::moduleHandler()->moduleExists('translation')) {
        $this->additional_fields['langcode'] = array('table' => 'feeds_feed', 'field' => 'langcode');
      }
    }
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_to_feed'] = array('default' => isset($this->definition['link_to_feed default']) ? $this->definition['link_to_feed default'] : FALSE, 'bool' => TRUE);
    return $options;
  }

  /**
   * Provides link to feed option.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['link_to_feed'] = array(
      '#title' => t('Link this field to the feed'),
      '#description' => t("Enable to override this field's links."),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_feed']),
    );

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * Render whatever the data is as a link to the feed.
   *
   * Data should be made XSS safe prior to calling this function.
   */
  protected function renderLink($data, ResultRow $values) {
    if (!empty($this->options['link_to_feed']) && !empty($this->additional_fields['fid'])) {
      if ($data !== NULL && $data !== '') {
        $this->options['alter']['make_link'] = TRUE;
        $this->options['alter']['path'] = 'feed/' . $this->getValue($values, 'fid');
        if (isset($this->aliases['langcode'])) {
          $languages = language_list();
          $langcode = $this->getValue($values, 'langcode');
          if (isset($languages[$langcode])) {
            $this->options['alter']['language'] = $languages[$langcode];
          }
          else {
            unset($this->options['alter']['language']);
          }
        }
      }
      else {
        $this->options['alter']['make_link'] = FALSE;
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    return $this->renderLink($this->sanitizeValue($value), $values);
  }

}
