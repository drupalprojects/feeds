<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Fetcher\DirectoryFetcher.
 */

namespace Drupal\feeds\Plugin\feeds\Fetcher;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedPluginFormInterface;
use Drupal\feeds\FetcherResult;
use Drupal\feeds\Plugin\FetcherBase;

/**
 * Defines a directory fetcher.
 *
 * @Plugin(
 *   id = "directory",
 *   title = @Translation("Directory fetcher"),
 *   description = @Translation("Uses a directory, or file, on the server.")
 * )
 */
class DirectoryFetcher extends FetcherBase implements FeedPluginFormInterface, FormInterface {

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
    $state = $feed->state(FEEDS_FETCH);
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

    throw new \Exception(t('Resource is not a file or it is an empty directory: %source', array('%source' => $feed_config['source'])));
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
  public function feedForm(array $form, array &$form_state, FeedInterface $feed) {
    $feed_config = $feed->getConfigurationFor($this);

    $form['fetcher']['#tree'] = TRUE;
    $form['fetcher']['source'] = array(
      '#type' => 'textfield',
      '#title' => t('File'),
      '#description' => t('Specify a path to a file or a directory. Prefix the path with a scheme. Available schemes: @schemes.', array('@schemes' => implode(', ', $this->configuration['allowed_schemes']))),
      '#default_value' => empty($feed_config['source']) ? '' : $feed_config['source'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function feedFormValidate(array $form, array &$form_state, FeedInterface $feed) {
    $values =& $form_state['values']['fetcher'];
    $values['source'] = trim($values['source']);
    // Check if chosen url scheme is allowed.
    $scheme = file_uri_scheme($values['source']);
    if (!$scheme || !in_array($scheme, $this->configuration['allowed_schemes'])) {
      form_set_error('feeds][directory][source', t("The file needs to reside within the site's files directory, its path needs to start with scheme://. Available schemes: @schemes.", array('@schemes' => implode(', ', $this->configuration['allowed_schemes']))));
    }
    // Check wether the given path exists.
    elseif (!file_exists($values['source'])) {
      form_set_error('feeds][directory][source', t('The specified file or directory does not exist.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigurationDefaults() {
    return array(
      'allowed_extensions' => 'txt csv tsv xml opml',
      'allowed_schemes' => $this->getSchemes(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form['allowed_extensions'] = array(
      '#type' => 'textfield',
      '#title' => t('Allowed file extensions'),
      '#description' => t('Allowed file extensions for upload.'),
      '#default_value' => $this->configuration['allowed_extensions'],
    );
    $form['allowed_schemes'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Allowed schemes'),
      '#default_value' => $this->configuration['allowed_schemes'],
      '#options' => $this->getSchemeOptions(),
      '#description' => t('Select the schemes you want to allow for direct upload.'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    $values =& $form_state['values']['fetcher']['config'];
    $values['allowed_schemes'] = array_filter($values['allowed_schemes']);
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
