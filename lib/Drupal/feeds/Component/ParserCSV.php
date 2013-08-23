<?php

/**
 * @file
 * Contains \Drupal\feeds\Component\ParserCSV.
 *
 * Functions in this file are independent of the Feeds specific implementation.
 * Thanks to jpetso http://drupal.org/user/56020 for most of the code in this
 * file.
 */

namespace Drupal\feeds\Component;

/**
 * Functionality to parse CSV files into a two dimensional arrays.
 *
 * @todo Make the quote character configurable.
 * @todo Get rid of this and use PHP's native version. Can we?
 * @todo Unit tests galore.
 */
class ParserCSV {

  /**
   * The column delimeter.
   *
   * @var string
   */
  protected $delimiter = ',';

  /**
   * Whether or not the first line contains a header.
   *
   * @var bool
   *
   * @todo Make this an integer that allows skipping multiple lines.
   */
  protected $skipFirstLine = FALSE;

  /**
   * The header columns.
   *
   * @var array
   */
  protected $columnNames = array();

  /**
   * The amount of time the parser should run before quiting.
   *
   * @var int
   */
  protected $timeout = FALSE;

  /**
   * The position in the file to start from.
   *
   * @var int
   */
  protected $startByte = 0;

  /**
   * The limit of the number of lines to parse. 0 means there is not limit.
   *
   * @var int
   */
  protected $lineLimit = 0;

  /**
   * The byte position where the parsing stopped.
   *
   * @var int
   */
  protected $lastLinePos = 0;

  /**
   * Constructs a ParserCSV object.
   */
  public function __construct() {
    ini_set('auto_detect_line_endings', TRUE);
  }

  /**
   * Sets the column delimiter string.
   *
   * @param string $delimiter
   *   By default, the comma (',') is used as delimiter.
   */
  public function setDelimiter($delimiter) {
    $this->delimiter = $delimiter;
  }

  /**
   * Sets whether or not the parser should skip the first line.
   *
   * Set this to TRUE if the parser should skip the first line of the CSV text,
   * which might be desired if the first line contains the column names.
   * By default, this is set to false and the first line is not skipped.
   *
   * @param bool $skip_first_line
   *   Whether or not to skip the first line.
   */
  public function setSkipFirstLine($skip_first_line) {
    $this->skipFirstLine = $skip_first_line;
  }

  /**
   * Sets the column names of the CSV file.
   *
   * Specify an array of column names if you know them in advance, or false
   * (which is the default) to unset any prior column names. If no column names
   * are set, the parser will put each row into a simple numerically indexed
   * array. If column names are given, the parser will create arrays with
   * these column names as array keys instead.
   *
   * @param arary $column_names
   *   The names of the columns.
   */
  public function setColumnNames(array $column_names) {
    $this->columnNames = $column_names;
  }

  /**
   * Defines the time (in milliseconds) after which the parser stops parsing.
   *
   * The parser will stop even if it has not yet finished processing the CSV
   * data. If the timeout has been reached before parsing is done, the parse()
   * method will return an incomplete list of rows - a single row will never be
   * cut off in the middle. By default, no timeout (@p $timeout == FALSE) is
   * defined.
   *
   * You can check if the timeout has been reached by calling the
   * lastLinePos() method after parse() has been called.
   *
   * @param int $timeout
   *   The amount of time in milliseconds the parser should run.
   */
  public function setTimeout($timeout) {
    $this->timeout = $timeout;
  }

  /**
   * Defines the number of lines to parse in one operation.
   *
   * By default, all lines of a file are being parsed.
   *
   * @param int $lines
   *   The number of lines to parse in one operation.
   */
  public function setLineLimit($lines) {
    $this->lineLimit = $lines;
  }

  /**
   * Gets the byte number where the parser left off after last parse() call.
   *
   * @return int
   *   0 if all lines or no line has been parsed, the byte position of where a
   *   timeout or the line limit has been reached, otherwise. This position can
   *   be used to set the start byte for the next iteration after parse() has
   *   reached the timeout set with setTimeout() or the line limit set with
   *   setLineLimit().
   *
   * @see ParserCSV::setStartByte()
   */
  public function lastLinePos() {
    return $this->lastLinePos;
  }

  /**
   * Sets the byte where file should be started at.
   *
   * Useful when parsing a file in batches.
   *
   * @param int $start
   *   The byte position to start parsing.
   */
  public function setStartByte($start) {
    return $this->startByte = $start;
  }

  /**
   * Parses CSV files into a two dimensional array.
   *
   * @param \Iterator $line_iterator
   *   An Iterator object that yields line strings, e.g. ParserCSVIterator.
   *
   * @return array
   *   Two dimensional array that contains the data in the CSV file.
   */
  public function parse(\Iterator $line_iterator) {
    $skip_line = $this->skipFirstLine;
    $rows = array();

    $this->lastLinePos = 0;
    $max_time = $this->timeout ? microtime() + $this->timeout ? FALSE;
    $lines_parsed = 0;

    for ($line_iterator->rewind($this->startByte); $line_iterator->valid(); $line_iterator->next()) {

      // Make really sure we've got lines without trailing newlines.
      $line = trim($line_iterator->current(), "\r\n");

      // Skip empty lines.
      if (!$line) {
        continue;
      }
      // If the first line contains column names, skip it.
      if ($skip_line) {
        $skip_line = FALSE;
        continue;
      }

      // The actual parser. explode() is unfortunately not suitable because the
      // delimiter might be located inside a quoted field, and that would break
      // the field and/or require additional effort to re-join the fields.
      $quoted = FALSE;
      $current_index = 0;
      $current_field = '';
      $fields = array();

      // We must use strlen() as we're parsing byte by byte using strpos(), so
      // drupal_strlen() will not work properly.
      $line_length = strlen($line);
      while ($current_index <= $line_length) {
        if ($quoted) {
          $next_quote_index = strpos($line, '"', $current_index);

          if ($next_quote_index === FALSE) {
            // There's a line break before the quote is closed, so grab the rest
            // of this line and fetch the next line.
            $current_field .= substr($line, $current_index);
            $line_iterator->next();

            if (!$line_iterator->valid()) {
              // Whoa, an unclosed quote! Well whatever, let's just ignore
              // that shortcoming and record it nevertheless.
              $fields[] = $current_field;
              break;
            }
            // Ok, so, on with fetching the next line, as mentioned above.
            $current_field .= "\n";
            $line = trim($line_iterator->current(), "\r\n");
            $current_index = 0;
            continue;
          }

          // There's actually another quote in this line, find out whether it's
          // escaped or not.
          $current_field .= substr($line, $current_index, $next_quote_index - $current_index);

          if (isset($line[$next_quote_index + 1]) && $line[$next_quote_index + 1] === '"') {
            // Escaped quote, add a single one to the field and proceed quoted.
            $current_field .= '"';
            $current_index = $next_quote_index + 2;
          }
          else {
            // End of the quoted section, close the quote and let the
            // $quoted == FALSE block finalize the field.
            $quoted = FALSE;
            $current_index = $next_quote_index + 1;
          }
        }
        // $quoted == FALSE.
        else {
          // First, let's find out where the next character of interest is.
          $next_quote_index = strpos($line, '"', $current_index);
          $next_delimiter_index = strpos($line, $this->delimiter, $current_index);

          if ($next_quote_index === FALSE) {
            $next_index = $next_delimiter_index;
          }
          elseif ($next_delimiter_index === FALSE) {
            $next_index = $next_quote_index;
          }
          else {
            $next_index = min($next_quote_index, $next_delimiter_index);
          }

          if ($next_index === FALSE) {
            // This line is done, add the rest of it as last field.
            $current_field .= substr($line, $current_index);
            $fields[] = $current_field;
            break;
          }
          elseif ($line[$next_index] === $this->delimiter[0]) {
            $length = ($next_index + strlen($this->delimiter) - 1) - $current_index;
            $current_field .= substr($line, $current_index, $length);
            $fields[] = $current_field;
            $current_field = '';
            $current_index += $length + 1;
            // Continue with the next field.
          }
          // $line[$next_index] == '"'.
          else {
            $quoted = TRUE;
            $current_field .= substr($line, $current_index, $next_index - $current_index);
            $current_index = $next_index + 1;
            // Continue this field in the $quoted == TRUE block.
          }
        }
      }
      // End of CSV parser. We've now got all the fields of the line as strings
      // in the $fields array.
      if (!$this->columnNames) {
        $row = $fields;
      }
      else {
        $row = array();
        foreach ($this->columnNames as $column_name) {
          $field = array_shift($fields);
          $row[$column_name] = (string) $field;
        }
      }
      $rows[] = $row;

      // Quit parsing if timeout has been reached or requested lines have been
      // reached.
      if ($max_time && microtime() > $max_time) {
        $this->lastLinePos = $line_iterator->currentPosition();
        break;
      }
      $lines_parsed++;
      if ($this->lineLimit && $lines_parsed >= $this->lineLimit) {
        $this->lastLinePos = $line_iterator->currentPosition();
        break;
      }
    }

    return $rows;
  }

}
