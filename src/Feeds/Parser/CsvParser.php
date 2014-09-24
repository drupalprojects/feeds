<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Parser\CsvParser.
 */

namespace Drupal\feeds\Feeds\Parser;

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Component\CsvParser as CsvFileParser;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\DynamicItem;
use Drupal\feeds\Plugin\Type\ConfigurablePluginBase;
use Drupal\feeds\Plugin\Type\FeedPluginFormInterface;
use Drupal\feeds\Plugin\Type\Parser\ParserInterface;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResult;
use Drupal\feeds\StateInterface;

/**
 * Defines a CSV feed parser.
 *
 * @Plugin(
 *   id = "csv",
 *   title = @Translation("CSV"),
 *   description = @Translation("Parse CSV files.")
 * )
 */
class CsvParser extends ConfigurablePluginBase implements FeedPluginFormInterface, ParserInterface {

  /**
   * {@inheritdoc}
   */
  public function parse(FeedInterface $feed, FetcherResultInterface $fetcher_result) {
    $feed_config = $feed->getConfigurationFor($this);
    $state = $feed->getState(StateInterface::PARSE);

    if (!filesize($fetcher_result->getFilePath())) {
      throw new EmptyFeedException();
    }

    // Load and configure parser.
    $parser = CsvFileParser::createFromFilePath($fetcher_result->getFilePath())
      ->setDelimiter($feed_config['delimiter'] == 'TAB' ? "\t" : $feed_config['delimiter'])
      ->setHasHeader(!$feed_config['no_headers'])
      ->setLineLimit($this->importer->getLimit())
      ->setStartByte((int) $state->pointer);

    $header = !$feed_config['no_headers'] ? $parser->getHeader() : array();
    $result = new ParserResult();

    foreach ($parser->parse() as $row) {
      $item = new DynamicItem();
      foreach ($row as $delta => $cell) {
        $key = isset($header[$delta]) ? $header[$delta] : $delta;
        $item->set($key, $cell);
      }

      $result->addItem($item);
    }

    // Report progress.
    $state->total = filesize($fetcher_result->getFilePath());
    $state->pointer = $parser->lastLinePos();
    $state->progress($state->total, $state->pointer);

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingSources() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function sourceDefaults() {
    return array(
      'delimiter' => $this->configuration['delimiter'],
      'no_headers' => $this->configuration['no_headers'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildFeedForm(array $form, FormStateInterface $form_state, FeedInterface $feed) {
    $feed_config = $feed->getConfigurationFor($this);
    $form['parser']['#tree'] = TRUE;
    $form['parser']['#weight'] = -10;

    $form['parser']['delimiter'] = array(
      '#type' => 'select',
      '#title' => $this->t('Delimiter'),
      '#description' => $this->t('The character that delimits fields in the CSV file.'),
      '#options'  => array(
        ',' => ',',
        ';' => ';',
        'TAB' => 'TAB',
        '|' => '|',
        '+' => '+',
      ),
      '#default_value' => $feed_config['delimiter'],
    );
    $form['parser']['no_headers'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('No Headers'),
      '#description' => $this->t("Check if the imported CSV file does not start with a header row. If checked, mapping sources must be named '0', '1', '2' etc."),
      '#default_value' => $feed_config['no_headers'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'delimiter' => ',',
      'no_headers' => 0,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['delimiter'] = array(
      '#type' => 'select',
      '#title' => $this->t('Default delimiter'),
      '#description' => $this->t('Default field delimiter.'),
      '#options' => array(
        ',' => ',',
        ';' => ';',
        'TAB' => 'TAB',
        '|' => '|',
        '+' => '+',
      ),
      '#default_value' => $this->configuration['delimiter'],
    );
    $form['no_headers'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('No headers'),
      '#description' => $this->t('Check if the imported CSV file does not start with a header row. If checked, mapping sources must be named \'0\', \'1\', \'2\' etc.'),
      '#default_value' => $this->configuration['no_headers'],
    );

    return $form;
  }

}
