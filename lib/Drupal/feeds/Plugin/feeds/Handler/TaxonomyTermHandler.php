<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Handler\TaxonomyTermHandler.
 */

namespace Drupal\feeds\Plugin\feeds\Handler;

use Drupal\Component\Annotation\Plugin;
use Drupal\Component\Plugin\PluginBase;
use Drupal\feeds\Exception\ValidationException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedsParserResult;

/**
 * Handles special user entity operations.
 *
 * @Plugin(
 *   id = "taxonomy_term"
 * )
 */
class TaxonomyTermHandler extends PluginBase {

  protected $config;

  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->importer = $configuration['importer'];
  }

  public static function applies($processor) {
    return $processor->entityType() == 'taxonomy_term';
  }

  /**
   * Creates a new user account in memory and returns it.
   */
  public function newEntityValues(FeedInterface $feed, &$values) {
    $values['input_format'] = $this->importer->processor->getConfig('input_format');
  }

  /**
   * Implements parent::entityInfo().
   */
  public function entityInfoAlter(array &$info) {
    $info['label_plural'] = t('Terms');
  }

  /**
   * Validates a user account.
   */
  public function entityValidate($term) {
    if (drupal_strlen($term->label()) == 0) {
      throw new ValidationException(t('Term name missing.'));
    }
  }

  public function preSave($term) {
    if (isset($term->parent)) {
      if (is_array($term->parent) && count($term->parent) == 1) {
        $term->parent = reset($term->parent);
      }
      if ($term->id() && ($term->parent == $term->id() || (is_array($term->parent) && in_array($term->id(), $term->parent)))) {
        throw new ValidationException(t("A term can't be its own child. GUID:@guid", array('@guid' => $term->feeds_item->guid)));
      }
    }
  }

  /**
   * Return available mapping targets.
   */
  public function getMappingTargets(array &$targets) {
    $targets['name']['optional_unique'] = TRUE;
    $targets['parent']['optional_unique'] = TRUE;
    $targets['weight']['optional_unique'] = TRUE;
    $targets += array(
      'parentguid' => array(
        'name' => t('Parent: GUID'),
        'description' => t('The GUID of the parent taxonomy term.'),
        'optional_unique' => TRUE,
      ),
    );
  }

  /**
   * Get id of an existing feed item term if available.
   */
  public function existingEntityId(FeedInterface $feed, FeedsParserResult $result) {
    // The only possible unique target is name.
    foreach ($this->importer->processor->uniqueTargets($feed, $result) as $target => $value) {
      if ($target == 'name') {
        if ($tid = db_query("SELECT tid FROM {taxonomy_term_data} WHERE name = :name AND vid = :vid", array(':name' => $value, ':vid' => $this->importer->processor->bundle()))->fetchField()) {
          return $tid;
        }
      }
    }
    return 0;
  }

}
