<?php

/**
 * @file
 * Contains \Drupal\feeds\BatchScheduler.
 */

namespace Drupal\feeds;

use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Executes an import or clear using Drupal's batch API.
 */
class BatchScheduler implements SchedulerInterface {

  /**
   * The translation manager service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationManager;

  /**
   * Constructs a BatchScheduler object.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation_manager
   */
  public function __construct(TranslationInterface $translation_manager) {
    $this->translationManager = $translation_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function startImport(FeedInterface $feed) {
    $this->startJob('import', $this->t('Importing'));
  }

  /**
   * {@inheritdoc}
   */
  public function startClear(FeedInterface $feed) {
    $this->startJob('clear', $this->t('Deleting'));
  }

  /**
   * Starts a batch job.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The Feed that is being executed.
   * @param string $method
   *   The method to run. 'import', 'clear', 'expire'
   * @param string $title
   *   The title to display.
   */
  protected function startJob(FeedInterface $feed, $method, $title) {
    $batch = array(
      'title' => $title,
      'operations' => array(
        array('feeds_batch', array($method, $feed->id())),
      ),
    );

    batch_set($batch);
  }

  /**
   * Translates a string to the current language or to a given language.
   *
   * @param string $string
   *   A string containing the English string to translate.
   * @param array $args
   *   An associative array of replacements to make after translation. Based
   *   on the first character of the key, the value is escaped and/or themed.
   *   See \Drupal\Core\Utility\String::format() for details.
   * @param array $options
   *   An associative array of additional options, with the following elements:
   *   - 'langcode': The language code to translate to a language other than
   *      what is used to display the page.
   *   - 'context': The context the source string belongs to.
   *
   * @return string
   *   The translated string.
   */
  protected function t($string, array $args = array(), array $options = array()) {
    return $this->getTranslationManager()->translate($string, $args, $options);
  }

}
