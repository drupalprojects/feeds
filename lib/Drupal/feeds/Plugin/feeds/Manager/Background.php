<?php

/**
 * @file
 * Contains \Drupal\feeds\Plugin\feeds\Manager\Background.
 */

namespace Drupal\feeds\Plugin\feeds\Manager;

use Drupal\Component\Annotation\Plugin;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Annotation\Translation;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Guzzle\AsyncPlugin;
use Drupal\feeds\Plugin\ConfigurablePluginBase;
use Drupal\feeds\Plugin\ManagerInterface;
use Drupal\feeds\Utility\HttpRequest;

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
  public function startImport(FeedInterface $feed) {
    if ($this->configuration['process_in_background']) {
      $this->startBackgroundJob($feed, 'import');
    }
    else {
      $this->startBatchAPIJob($feed, t('Importing'), 'import');
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
      $this->startBatchAPIJob($feed, t('Deleting'), 'clear');
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
    $token = Crypt::randomStringHashed(55);

    \Drupal::state()->set($cid, array(
      'token' => $token,
      'method' => $method,
    ));

    $client = \Drupal::httpClient();

    // Do not wait for a response.
    $client->addSubscriber(new AsyncPlugin());

    $request = $client->post(url('feed/' . $feed->id() . '/execute', array('absolute' => TRUE)))
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

  public function sourceSave(FeedInterface $feed) {
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    $cron_required = ' ' . l(t('Requires cron to be configured.'), 'http://drupal.org/cron', array('attributes' => array('target' => '_new')));

    $form['import_on_create'] = array(
      '#type' => 'checkbox',
      '#title' => t('Import on submission'),
      '#description' => t('Check if import should be started at the moment a standalone form or node form is submitted.'),
      '#default_value' => $this->configuration['import_on_create'],
    );

    $form['process_in_background'] = array(
      '#type' => 'checkbox',
      '#title' => t('Process in background'),
      '#description' => t('For very large imports. If checked, import and delete tasks started from the web UI will be handled by a cron task in the background rather than by the browser. This does not affect periodic imports, they are handled by a cron task in any case.') . $cron_required,
      '#default_value' => $this->configuration['process_in_background'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, array &$form_state) {
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultConfiguration() {
    return array('process_in_background' => FALSE, 'import_on_create' => TRUE);
  }

}
