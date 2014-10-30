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
    // Just return a file fetcher result if this is a file.
    if (is_file($path)) {
      return new FetcherResult($path);
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
    $form['source']['widget'][0]['value']['#type'] = 'feeds_uri';
    $form['source']['widget'][0]['value']['#allowed_schemes'] = $this->configuration['allowed_schemes'];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateFeedForm(array &$form, FormStateInterface $form_state, FeedInterface $feed) {
    $source = $form_state->getValue(['source', 0, 'value']);

    // Check wether the given path exists.
    if (!is_readable($source) || (!is_dir($source) && !is_file($source))) {
      $form_state->setError($form['source'], $this->t('%source is not a readable directory or file.', ['%source' => $source]));
    }
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
