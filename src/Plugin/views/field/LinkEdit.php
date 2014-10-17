<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\views\field\LinkEdit.
 */

namespace Drupal\feeds\Plugin\views\field;

use Drupal\views\ResultRow;

/**
 * Field handler to present a link feed edit.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("feeds_feed_link_edit")
 */
class LinkEdit extends Link {

  /**
   * Renders the link.
   */
  protected function renderLink($feed, ResultRow $values) {
    // Ensure user has access to edit this feed.
    if (!$feed->access('update')) {
      return;
    }

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = $feed->getSystemPath('edit-from');
    $this->options['alter']['query'] = drupal_get_destination();

    return !empty($this->options['text']) ? $this->options['text'] : t('Edit');
  }

}
