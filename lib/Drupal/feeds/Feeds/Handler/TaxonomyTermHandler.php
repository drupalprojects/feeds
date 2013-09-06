<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Handler\TaxonomyTermHandler.
 */

namespace Drupal\feeds\Feeds\Handler;

use Drupal\Component\Annotation\Plugin;
use Drupal\Component\Utility\String;
use Drupal\feeds\Exception\ValidationException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\PluginBase;

/**
 * Handles special user entity operations.
 *
 * @Plugin(id = "taxonomy_term")
 */
class TaxonomyTermHandler extends PluginBase {

  public static function applies($processor) {
    return $processor->entityType() == 'taxonomy_term';
  }

  /**
   * Creates a new user account in memory and returns it.
   */
  public function newEntityValues(FeedInterface $feed, &$values) {
    $values['input_format'] = $this->importer->getProcessor()->getConfiguration('input_format');
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultConfiguration() {
    return array('format' => 'plain_text');
  }

  public function buildConfigurationForm(array &$form, array &$form_state) {
    global $user;
    $options = array();
    foreach (filter_formats($user) as $id => $format) {
      $options[$id] = $format->label();
    }

    $form['format'] = array(
      '#type' => 'select',
      '#title' => $this->t('Description format'),
      '#options' => $options,
      '#default_value' => $this->configuration['format'],
    );
  }

  /**
   * Implements parent::entityInfo().
   */
  public function entityInfo(array &$info) {
    $info['label_plural'] = $this->t('Taxonomy terms');
  }

  /**
   * {@inheritdoc}
   */
  public function entityValidate($term) {
    if (isset($term->parent)) {
      if (is_array($term->parent) && count($term->parent) == 1) {
        $term->parent = reset($term->parent);
      }
      if ($term->id() && ($term->parent == $term->id() || (is_array($term->parent) && in_array($term->id(), $term->parent)))) {
        throw new ValidationException(String::format("A term can't be its own child. GUID:@guid", array('@guid' => $term->feeds_item->guid)));
      }
    }

    $term->get('format')->value = $this->configuration['format'];
  }

}
