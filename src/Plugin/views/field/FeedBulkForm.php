<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\views\field\FeedBulkForm.
 */

namespace Drupal\feeds\Plugin\views\field;

use Drupal\system\Plugin\views\field\BulkForm;

/**
 * Defines a feed operations bulk form element.
 *
 * @ViewsField("feeds_feed_bulk_form")
 */
class FeedBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No feeds selected.');
  }

}
