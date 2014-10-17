<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\views\field\LinkDelete.
 */

namespace Drupal\feeds\Plugin\views\field;

use Drupal\views\ResultRow;

/**
 * Field handler to present a link to delete a feed.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("feeds_feed_link_delete")
 */
class LinkDelete extends Link {

  /**
   * Renders the link.
   */
  protected function renderLink($feed, ResultRow $values) {
    // Ensure user has access to delete this feed.
    if (!$feed->access('delete')) {
      return;
    }

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = $feed->getSystemPath('delete-from');
    $this->options['alter']['query'] = drupal_get_destination();

    return !empty($this->options['text']) ? $this->options['text'] : t('Delete');
  }

}
