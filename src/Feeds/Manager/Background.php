<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Manager\Background.
 */

namespace Drupal\feeds\Feeds\Manager;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Guzzle\AsyncPlugin;
use Drupal\feeds\Plugin\Type\ConfigurablePluginBase;
use Drupal\feeds\Plugin\Type\Manager\ManagerInterface;

/**
 * Defines a Feeds manager plugin that performs background jobs.
 *
 * @Plugin(
 *   id = "background",
 *   title = @Translation("Background"),
 *   description = @Translation("Executes jobs in the background.")
 * )
 */
class Background extends ConfigurablePluginBase implements ManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function onFeedSave(FeedInterface $feed, $update = TRUE) {
    if (!$update && $this->configuration['import_on_create']) {
      $this->startImport($feed);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function startImport(FeedInterface $feed) {
    if ($this->configuration['process_in_background']) {
      $this->startBackgroundJob($feed, 'import');
    }
    else {
      $batch = array(
        'title' => $this->t('Importing %title', array('%title' => $feed->label())),
        'init_message' => $this->t('Starting feed import.'),
        'operations' => array(
          array('feeds_batch', array('import', $feed->id())),
        ),
        'progress_message' => $this->t('Importing %title', array('%title' => $feed->label())),
        'finished' => array(get_class($this), 'finishBatch'),
        'error_message' => $this->t('An error occored while importing %title.', array('%title' => $feed->label())),
      );

      batch_set($batch);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function startClear(FeedInterface $feed) {
    if ($this->configuration['process_in_background']) {
      $this->startBackgroundJob($feed, 'clear');
    }
    else {
      $batch = array(
        'title' => $this->t('Deleting %title', array('%title' => $feed->label())),
        'init_message' => $this->t('Starting feed clear.'),
        'operations' => array(
          array('feeds_batch', array('clear', $feed->id())),
        ),
        'progress_message' => $this->t('Deleting %title', array('%title' => $feed->label())),
        'finished' => array(get_class($this), 'finishBatch'),
        'error_message' => $this->t('An error occored while deleting %title.', array('%title' => $feed->label())),
      );

      batch_set($batch);
    }
  }

  /**
   * Starts a background job using a new process.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to start the job for.
   * @param string $method
   *   Method to execute on importer; one of 'import' or 'clear'.
   *
   * @throws Exception $e
   *
   * @todo Inject these dependencies.
   */
  protected function startBackgroundJob(FeedInterface $feed, $method) {
    $cid = 'feeds_feed:' . $feed->id();
    $token = Crypt::randomBytesBase64(55);

    \Drupal::state()->set($cid, array(
      'token' => $token,
      'method' => $method,
    ));

    $client = \Drupal::httpClient();

    // Do not wait for a response.
    // $client->addSubscriber(new AsyncPlugin());
    $url = $this->url('feeds.execute', array('feeds_feed' => $feed->id()), array('absolute' => TRUE));
    $request = $client->post($url)
      ->addPostFields(array('token' => $token));

    $request->send();
  }

  /**
   * Starts a Batch API job.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to start the job for.
   * @param string $title
   *   Title to show to user when executing batch.
   * @param string $method
   *   Method to execute on importer; one of 'import' or 'clear'.
   */
  protected function startBatchAPIJob(FeedInterface $feed, $title, $method) {
    $batch = array(
      'title' => $title,
      'operations' => array(
        array('feeds_batch', array($method, $feed->id())),
      ),
      'progress_message' => '',
    );

    batch_set($batch);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['import_on_create'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Import on submission'),
      '#description' => $this->t('Check if import should be started at the moment a standalone form or node form is submitted.'),
      '#default_value' => $this->configuration['import_on_create'],
    );

    $form['process_in_background'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Process in background'),
      '#description' => $this->t('For very large imports. If checked, import and delete tasks started from the web UI will be handled by a cron task in the background rather than by the browser.'),
      '#default_value' => $this->configuration['process_in_background'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array('process_in_background' => FALSE, 'import_on_create' => TRUE);
  }

  /**
   * Finish batch.
   *
   * This function is a static function to avoid serialising the Background
   * object unnecessarily.
   */
  public static function finishBatch($success, $results, $operations) {
    if ($success) {

    }
    else {

    }
  }

}
