<?php

/**
 * @file
 * Contains \Drupal\feeds\FeedsPermissions.
 */

namespace Drupal\feeds;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\feeds\Entity\Importer;

/**
 * Defines a class containing permission callbacks.
 */
class FeedsPermissions {

  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * Returns an array of content permissions.
   *
   * @return array
   */
  public function contentPermissions() {
    return array(
      'access feed overview' => array(
        'title' => $this->t('Access the Feed overview page'),
        'description' => $this->t('Get an overview of <a href="!url">all feeds</a>.', array('!url' => $this->url('feeds.admin'))),
      ),
    );
  }

  /**
   * Returns an array of feeds type permissions.
   *
   * @return array
   */
  public function importerPermissions() {
    $perms = [];
    // Generate feeds permissions for all feeds types.
    foreach (Importer::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Builds a standard list of feeds permissions for a given type.
   *
   * @param \Drupal\feeds\Entity\Importer $importer
   *   The importer object.
   *
   * @return array
   *   An array of permission names and descriptions.
   */
  protected function buildPermissions(Importer $importer) {
    $args = array('%name' => $importer->label());
    $id = $importer->id();

    return array(
      "view $id feeds" => array(
        'title' => $this->t('%name: View feeds', $args),
      ),
      "create $id feeds" => array(
        'title' => $this->t('%name: Create new feeds', $args),
      ),
      "update $id feeds" => array(
        'title' => $this->t('%name: Update existing feeds', $args),
      ),
      "delete $id feeds" => array(
        'title' => $this->t('%name: Delete feeds', $args),
      ),
      "import $id feeds" => array(
        'title' => $this->t('%name: Import feeds', $args),
      ),
      "clear $id feeds" => array(
        'title' => $this->t('%name: Delete feed items', $args),
      ),
      "unlock $id feeds" => array(
        'title' => $this->t('%name: Unlock feeds', $args),
        'description' => $this->t('If a feed importation breaks for some reason, users with this permission can unlock it.'),
      ),
    );
  }

}
