<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Parser\CSVParser.
 */

namespace Drupal\feeds\Plugin\feeds\Parser;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\feeds\Component\ParserCSV;
use Drupal\feeds\Component\ParserCSVIterator;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedPluginFormInterface;
use Drupal\feeds\Result\ParserResult;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Plugin\ConfigurablePluginBase;
use Drupal\feeds\Plugin\ParserInterface;
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
class CSVParser extends ConfigurablePluginBase implements FeedPluginFormInterface, ParserInterface {

  /**
   * {@inheritdoc}
   */
  public function parse(FeedInterface $feed, FetcherResultInterface $fetcher_result) {
    $feed_config = $feed->getConfigurationFor($this);
    $state = $feed->state(StateInterface::PARSE);

    // Load and configure parser.
    $parser = new ParserCSV();
    $delimiter = $feed_config['delimiter'] == 'TAB' ? "\t" : $feed_config['delimiter'];
    $parser->setDelimiter($delimiter);

    $iterator = new ParserCSVIterator($fetcher_result->getFilePath());
    if (empty($feed_config['no_headers'])) {
      // Get first line and use it for column names, convert them to lower case.
      $header = $this->parseHeader($parser, $iterator);
      if (!$header) {
        return;
      }
      $parser->setColumnNames($header);
    }

    // Determine section to parse, parse.
    $start = $state->pointer ? $state->pointer : $parser->lastLinePos();
    $limit = $this->importer->getLimit();
    $rows = $this->parseItems($parser, $iterator, $start, $limit);

    // Report progress.
    $state->total = filesize($fetcher_result->getFilePath());
    $state->pointer = $parser->lastLinePos();
    $progress = $parser->lastLinePos() ? $parser->lastLinePos() : $state->total;
    $state->progress($state->total, $progress);

    // Create a result object and return it.
    return new ParserResult($rows, $feed->id());
  }

  /**
   * Get first line and use it for column names, convert them to lower case.
   * Be aware that the $parser and iterator objects can be modified in this
   * function since they are passed in by reference
   *
   * @param ParserCSV $parser
   * @param ParserCSVIterator $iterator
   * @return
   *   An array of lower-cased column names to use as keys for the parsed items.
   */
  protected function parseHeader(ParserCSV $parser, ParserCSVIterator $iterator) {
    $parser->setLineLimit(1);
    $rows = $parser->parse($iterator);
    if (!count($rows)) {
      return FALSE;
    }
    $header = array_shift($rows);
    foreach ($header as $i => $title) {
      $header[$i] = trim(drupal_strtolower($title));
    }
    return $header;
  }

  /**
   * Parse all of the items from the CSV.
   *
   * @param ParserCSV $parser
   * @param ParserCSVIterator $iterator
   * @return
   *   An array of rows of the CSV keyed by the column names previously set
   */
  protected function parseItems(ParserCSV $parser, ParserCSVIterator $iterator, $start = 0, $limit = 0) {
    $parser->setLineLimit($limit);
    $parser->setStartByte($start);
    $rows = $parser->parse($iterator);
    return $rows;
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
  public function getSourceElement(FeedInterface $feed, array $item, $element_key) {
    return parent::getSourceElement($feed, $item, drupal_strtolower($element_key));
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
  public function buildFeedForm(array $form, array &$form_state, FeedInterface $feed) {
    $feed_config = $feed->getConfigurationFor($this);
    $form['parser']['#tree'] = TRUE;
    $form['parser']['#weight'] = -10;

    $mappings = $this->importer->getMappings();
    $feeds = $uniques = array();
    foreach ($mappings as $mapping) {
      $feeds[] = check_plain($mapping['source']);
      if (!empty($mapping['unique'])) {
        $uniques[] = check_plain($mapping['source']);
      }
    }

    $output = $this->t('Import !csv_files with one or more of these columns: !columns.', array('!csv_files' => l($this->t('CSV files'), 'http://en.wikipedia.org/wiki/Comma-separated_values'), '!columns' => implode(', ', $feeds)));
    $items = array();
    $items[] = format_plural(count($uniques), $this->t('Column <strong>!column</strong> is mandatory and considered unique: only one item per !column value will be created.', array('!column' => implode(', ', $uniques))), $this->t('Columns <strong>!columns</strong> are mandatory and values in these columns are considered unique: only one entry per value in one of these column will be created.', array('!columns' => implode(', ', $uniques))));
    $items[] = l($this->t('Download a template'), 'import/' . $this->importer->id() . '/template');
    $form['parser']['help'] = array(
      '#prefix' => '<div class="help">',
      '#suffix' => '</div>',
      'description' => array(
        '#prefix' => '<p>',
        '#markup' => $output,
        '#suffix' => '</p>',
      ),
      'list' => array(
        '#theme' => 'item_list',
        '#items' => $items,
      ),
    );
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
      '#default_value' => isset($feed_config['delimiter']) ? $feed_config['delimiter'] : ',',
    );
    $form['parser']['no_headers'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('No Headers'),
      '#description' => $this->t('Check if the imported CSV file does not start with a header row. If checked, mapping sources must be named \'0\', \'1\', \'2\' etc.'),
      '#default_value' => isset($feed_config['no_headers']) ? $feed_config['no_headers'] : 0,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultConfiguration() {
    return array(
      'delimiter' => ',',
      'no_headers' => 0,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
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

  public function getTemplate() {
    $mappings = $this->importer->getMappings();
    $feeds = $uniques = array();
    foreach ($mappings as $mapping) {
      if (!empty($mapping['unique'])) {
        $uniques[] = check_plain($mapping['source']);
      }
      else {
        $feeds[] = check_plain($mapping['source']);
      }
    }
    $sep = ',';
    $columns = array();
    foreach (array_merge($uniques, $feeds) as $col) {
      if (strpos($col, $sep) !== FALSE) {
        $col = '"' . str_replace('"', '""', $col) . '"';
      }
      $columns[] = $col;
    }
    drupal_add_http_header('Cache-Control', 'max-age=60, must-revalidate');
    drupal_add_http_header('Content-Disposition', 'attachment; filename="' . $this->importer->id() . '_template.csv"');
    drupal_add_http_header('Content-type', 'text/csv; charset=utf-8');
    print implode($sep, $columns);
    return;
  }

}
