<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Fetcher\DirectoryFetcher.
 */

namespace Drupal\feeds\Feeds\Fetcher;

use Drupal\Component\Utility\String;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\ConfigurablePluginBase;
use Drupal\feeds\Plugin\Type\FeedPluginFormInterface;
use Drupal\feeds\Plugin\Type\Fetcher\FetcherInterface;
use Drupal\feeds\Result\FetcherResult;
use Drupal\feeds\StateInterface;

/**
 * Defines a directory fetcher.
 *
 * @Plugin(
 *   id = "directory",
 *   title = @Translation("Directory"),
 *   description = @Translation("Uses a directory, or file, on the server.")
 * )
 */
class DirectoryFetcher extends ConfigurablePluginBase implements FetcherInterface, FeedPluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function fetch(FeedInterface $feed) {
    $feed_config = $feed->getConfigurationFor($this);

    // Just return a file fetcher result if this is a file.
    if (is_file($feed_config['source'])) {
      return new FetcherResult($feed_config['source']);
    }

    // Batch if this is a directory.
    $state = $feed->getState(StateInterface::FETCH);
    $files = array();
    if (!isset($state->files)) {
      $state->files = $this->listFiles($feed_config['source']);
      $state->total = count($state->files);
    }
    if ($state->files) {
      $file = array_shift($state->files);
      $state->progress($state->total, $state->total - count($state->files));
      return new FetcherResult($file);
    }

    // @todo Better exception.
    throw new \Exception(String::format('Resource is not a file or it is an empty directory: %source', array('%source' => $feed_config['source'])));
  }

  /**
   * Returns an array of files in a directory.
   *
   * @param string $dir
   *   A stream wreapper URI that is a directory.
   *
   * @return array
   *   An array of stream wrapper URIs pointing to files. The array is empty if
   *   no files could be found. Never contains directories.
   */
  protected function listFiles($dir) {
    $dir = file_stream_wrapper_uri_normalize($dir);
    $files = array();
    if ($items = @scandir($dir)) {
      foreach ($items as $item) {
        if (is_file("$dir/$item") && strpos($item, '.') !== 0) {
          $files[] = "$dir/$item";
        }
      }
    }
    return $files;
  }

  /**
   * {@inheritdoc}
   */
  public function sourceDefaults() {
    return array('source' => '');
  }

  /**
   * {@inheritdoc}
   */
  public function buildFeedForm(array $form, FormStateInterface $form_state, FeedInterface $feed) {
    $feed_config = $feed->getConfigurationFor($this);

    $form['fetcher']['#tree'] = TRUE;
    $form['fetcher']['source'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('File'),
      '#description' => $this->t('Specify a path to a file or a directory. Prefix the path with a scheme. Available schemes: @schemes.', array('@schemes' => implode(', ', $this->configuration['allowed_schemes']))),
      '#default_value' => $feed_config['source'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateFeedForm(array &$form, FormStateInterface $form_state, FeedInterface $feed) {
    $values =& $form_state['values']['fetcher'];
    $values['source'] = trim($values['source']);
    // Check if chosen url scheme is allowed.
    $scheme = file_uri_scheme($values['source']);
    if (!$scheme || !in_array($scheme, $this->configuration['allowed_schemes'])) {
      form_set_error('feeds][directory][source', $this->t("The file needs to reside within the site's files directory, its path needs to start with scheme://. Available schemes: @schemes.", array('@schemes' => implode(', ', $this->configuration['allowed_schemes']))));
    }
    // Check wether the given path exists.
    elseif (!file_exists($values['source'])) {
      form_set_error('feeds][directory][source', $this->t('The specified file or directory does not exist.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'allowed_extensions' => array('txt', 'csv', 'tsv', 'xml', 'opml'),
      'allowed_schemes' => $this->getSchemes(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['allowed_extensions'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Allowed file extensions'),
      '#description' => $this->t('Allowed file extensions for upload.'),
      '#default_value' => implode(' ', $this->configuration['allowed_extensions']),
    );
    $form['allowed_schemes'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed schemes'),
      '#default_value' => $this->configuration['allowed_schemes'],
      '#options' => $this->getSchemeOptions(),
      '#description' => $this->t('Select the schemes you want to allow for direct upload.'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values =& $form_state['values']['fetcher']['configuration'];
    $values['allowed_schemes'] = array_filter($values['allowed_schemes']);
    // Convert allowed_extensions to an array for storage.
    $values['allowed_extensions'] = explode(' ', preg_replace('/\s+/', ' ', trim($values['allowed_extensions'])));
    $values['allowed_extensions'] = array_unique($values['allowed_extensions']);
  }

  /**
   * Returns available schemes.
   *
   * @return array
   *   The available schemes.
   */
  protected function getSchemes() {
    return array_keys(file_get_stream_wrappers(STREAM_WRAPPERS_WRITE_VISIBLE));
  }

  /**
   * Returns available scheme options for use in checkboxes or select list.
   *
   * @return array
   *   The available scheme array keyed scheme => description.
   */
  protected function getSchemeOptions() {
    $options = array();
    foreach (file_get_stream_wrappers(STREAM_WRAPPERS_WRITE_VISIBLE) as $scheme => $info) {
      $options[$scheme] = check_plain($scheme . ': ' . $info['description']);
    }
    return $options;
  }

}
