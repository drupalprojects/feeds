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
use Drupal\feeds\Plugin\PluginBase;
use Drupal\feeds\Result\ParserResultInterface;

/**
 * Handles special user entity operations.
 *
 * @Plugin(
 *   id = "taxonomy_term"
 * )
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
   * Implements parent::entityInfo().
   */
  public function entityInfo(array &$info) {
    $info['label_plural'] = $this->t('Taxonomy terms');
  }

  /**
   * Validates a user account.
   */
  public function entityValidate($term) {
    if (drupal_strlen($term->label()) == 0) {
      throw new ValidationException('Term name missing.');
    }
  }

  public function preSave($term) {
    if (isset($term->parent)) {
      if (is_array($term->parent) && count($term->parent) == 1) {
        $term->parent = reset($term->parent);
      }
      if ($term->id() && ($term->parent == $term->id() || (is_array($term->parent) && in_array($term->id(), $term->parent)))) {
        throw new ValidationException(String::format("A term can't be its own child. GUID:@guid", array('@guid' => $term->feeds_item->guid)));
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
        'name' => $this->t('Parent: GUID'),
        'description' => $this->t('The GUID of the parent taxonomy term.'),
        'optional_unique' => TRUE,
      ),
    );
  }

  /**
   * Get id of an existing feed item term if available.
   */
  public function existingEntityId(FeedInterface $feed, array $item) {
    // The only possible unique target is name.
    foreach ($this->importer->getProcessor()->uniqueTargets($feed, $item) as $target => $value) {
      if ($target == 'name') {
        if ($tid = db_query("SELECT tid FROM {taxonomy_term_data} WHERE name = :name AND vid = :vid", array(':name' => $value, ':vid' => $this->importer->getProcessor()->bundle()))->fetchField()) {
          return $tid;
        }
      }
    }
    return 0;
  }

}
