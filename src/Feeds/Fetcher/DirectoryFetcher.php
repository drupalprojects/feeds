<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Fetcher\DirectoryFetcher.
 */

namespace Drupal\feeds\Feeds\Fetcher;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\FeedPluginFormInterface;
use Drupal\feeds\Plugin\Type\Fetcher\FetcherInterface;
use Drupal\feeds\Plugin\Type\PluginBase;
use Drupal\feeds\Result\FetcherResult;
use Drupal\feeds\StateInterface;

/**
 * Defines a directory fetcher.
 *
 * @FeedsFetcher(
 *   id = "directory",
 *   title = @Translation("Directory"),
 *   description = @Translation("Uses a directory, or file, on the server."),
 *   configuration_form = "Drupal\feeds\Feeds\Fetcher\Form\DirectoryFetcherForm"
 * )
 */
class DirectoryFetcher extends PluginBase implements FetcherInterface, FeedPluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function fetch(FeedInterface $feed, StateInterface $state) {
    $path = $feed->getSource();
    // Just return a file fetcher result if this is a file. Make sure to
    // re-validate the file extension in case the feed type settings have
    // changed.
    if (is_file($path)) {
      if ($this->validateFilePath($path)) {
        return new FetcherResult($path);
      }
      else {
        throw new \RuntimeException($this->t('%source has an invalid file extension.', ['%source' => $path]));
      }
    }

    if (!is_dir($path) || !is_readable($path)) {
      throw new \RuntimeException($this->t('%source is not a readable directory or file.', ['%source' => $path]));
    }

    // Batch if this is a directory.
    if (!isset($state->files)) {
      $state->files = $this->listFiles($path);
      $state->total = count($state->files);
    }
    if ($state->files) {
      $file = array_shift($state->files);
      $state->progress($state->total, $state->total - count($state->files));
      return new FetcherResult($file);
    }

    throw new EmptyFeedException();
  }

  /**
   * Validates a single file path.
   *
   * @param string $filepath
   *   The file path.
   *
   * @return bool
   *   Returns true if the file is valid, and false if not.
   */
  protected function validateFilePath($filepath) {
    $filename = drupal_basename($filepath);
    // Don't allow hidden files.
    if (substr($filename, 0, 1) === '.') {
      return FALSE;
    }
    // Validate file extension.
    $extension = substr($filename, strrpos($filename, '.') + 1);
    return in_array($extension, $this->configuration['allowed_extensions'], TRUE);
  }

  /**
   * Returns an array of files in a directory.
   *
   * @param string $dir
   *   A stream wreapper URI that is a directory.
   *
   * @return string[]
   *   An array of stream wrapper URIs pointing to files.
   */
  protected function listFiles($dir) {
    $flags =
      \FilesystemIterator::KEY_AS_PATHNAME |
      \FilesystemIterator::CURRENT_AS_FILEINFO |
      \FilesystemIterator::SKIP_DOTS;
    $extensions = array_flip($this->configuration['allowed_extensions']);

    if ($this->configuration['recursive_scan']) {
      $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, $flags));
    }
    else {
      $iterator = new \FilesystemIterator($dir, $flags);
    }

    $files = [];
    foreach ($iterator as $path => $file) {
      if ($file->isFile() && isset($extensions[$file->getExtension()]) && $file->isReadable()) {
        $files[] = $path;
      }
    }

    return $files;
  }

  /**
   * {@inheritdoc}
   */
  public function sourceDefaults() {
    return ['source' => ''];
  }

  /**
   * {@inheritdoc}
   */
  public function buildFeedForm(array $form, FormStateInterface $form_state, FeedInterface $feed) {
    $form['source'] = [
      '#title' => $this->t('Server file or directory path'),
      '#type' => 'feeds_uri',
      '#default_value' => $feed->getSource(),
      '#allowed_schemes' => $this->configuration['allowed_schemes'],
      '#description' => $this->t('The allowed schemes are: %schemes', ['%schemes' => implode(', ', $this->configuration['allowed_schemes'])]),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateFeedForm(array &$form, FormStateInterface $form_state, FeedInterface $feed) {
    $source = $form_state->getValue('source');

    if (!is_readable($source) || (!is_dir($source) && !is_file($source))) {
      $form_state->setError($form['source'], $this->t('%source is not a readable directory or file.', ['%source' => $source]));
      return;
    }
    if (is_dir($source)) {
      return;
    }
    // Validate a single file.
    if (!$this->validateFilePath($source)) {
      $form_state->setError($form['source'], $this->t('%source has an invalid file extension.', ['%source' => $source]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitFeedForm(array &$form, FormStateInterface $form_state, FeedInterface $feed) {
    $feed->setSource($form_state->getValue('source'));
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'allowed_extensions' => ['txt', 'csv', 'tsv', 'xml', 'opml'],
      'allowed_schemes' => ['public'],
      'recursive_scan' => FALSE,
    ];
  }

}
