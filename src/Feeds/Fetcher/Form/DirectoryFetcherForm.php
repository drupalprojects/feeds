<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Fetcher\Form\DirectoryFetcherForm.
 */

namespace Drupal\feeds\Feeds\Fetcher\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\feeds\Plugin\Type\ExternalPluginFormBase;
use Drupal\feeds\Plugin\Type\FeedsPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a directory fetcher.
 */
class DirectoryFetcherForm extends ExternalPluginFormBase {

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManager
   */
  protected $streamWrapperManager;

  /**
   * Constructs a DirectoryFetcherForm object.
   *
   * @param \Drupal\feeds\Plugin\Type\FeedsPluginInterface $plugin
   *   The feeds plugin.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManager $stream_wrapper_manager
   *   The stream wrapper manager.
   */
  public function __construct(FeedsPluginInterface $plugin, StreamWrapperManager $stream_wrapper_manager) {
    $this->plugin = $plugin;
    $this->streamWrapperManager = $stream_wrapper_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, FeedsPluginInterface $plugin) {
    return new static($plugin, $container->get('stream_wrapper_manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['allowed_extensions'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed file extensions'),
      '#description' => $this->t('Allowed file extensions for upload.'),
      '#default_value' => implode(' ', $this->plugin->getConfiguration('allowed_extensions')),
    ];
    $form['allowed_schemes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed schemes'),
      '#default_value' => $this->plugin->getConfiguration('allowed_schemes'),
      '#options' => $this->getSchemeOptions(),
      '#description' => $this->t('Select the schemes you want to allow for direct upload.'),
    ];
    $form['recursive_scan'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Search recursively'),
      '#default_value' => $this->plugin->getConfiguration('recursive_scan'),
      '#description' => $this->t('Search through sub-directories.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values =& $form_state->getValues();
    $values['allowed_schemes'] = array_filter($values['allowed_schemes']);
    // Convert allowed_extensions to an array for storage.
    $values['allowed_extensions'] = array_unique(explode(' ', preg_replace('/\s+/', ' ', trim($values['allowed_extensions']))));
  }

  /**
   * Returns available scheme options for use in checkboxes or select list.
   *
   * @return array
   *   The available scheme array keyed scheme => description.
   */
  protected function getSchemeOptions() {
    $options = [];
    foreach ($this->streamWrapperManager->getDescriptions(StreamWrapperInterface::WRITE_VISIBLE) as $scheme => $description) {
      $options[$scheme] = SafeMarkup::checkPlain($scheme . ': ' . $description);
    }
    return $options;
  }

}
