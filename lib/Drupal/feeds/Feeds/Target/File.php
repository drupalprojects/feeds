<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Target\File.
 */

namespace Drupal\feeds\Feeds\Target;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Language\Language;
use Drupal\feeds\FeedsEnclosure;
use Drupal\feeds\Plugin\FieldTargetBase;
use Drupal\feeds\Plugin\ConfigurableTargetInterface;

/**
 * Defines a file field mapper.
 *
 * @Plugin(
 *   id = "file",
 *   field_types = {"file", "image"}
 * )
 */
class File extends FieldTargetBase implements ConfigurableTargetInterface {

  /**
   * The file upload directory.
   *
   * @var string
   */
  protected $uploadDirectory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $settings, $plugin_id, array $plugin_definition) {
    parent::__construct($settings, $plugin_id, $plugin_definition);

    // Calculate the upload directory.
    if (isset($this->settings['instance'])) {
      $this->uploadDirectory = $this->settings['instance']->getFieldSetting('uri_scheme');
      $this->uploadDirectory .= '://' . $this->settings['instance']->getFieldSetting('file_directory');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(array &$target) {
    $target['properties']['target_id']['label'] = t('Filepath, either a remote URL or local file.');
    unset($target['properties']['revision_id']);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    foreach ($values as $column => $value) {
      switch ($column) {
        case 'alt':
        case 'title':
          $values[$column] = (string) $value;
          break;

        case 'width':
        case 'height':
          $values[$column] = '';
          if ($value = (int) trim((string) $value)) {
            $values[$column] = $value;
          }
          break;

        case 'target_id':
          $values[$column] = $this->getFile($value);
          break;

        case 'display':
          $values[$column] = 1;
          break;
      }
    }
  }

  /**
   * Returns a file id given a url.
   *
   * @param string|\Drupal\feeds\FeedsEnclosure $value
   *   A URL string or FeedsEnclosure object.
   *
   * @return int
   *   The file id.
   */
  protected function getFile($value) {
    if (!($value instanceof FeedsEnclosure)) {
      if (is_string($value)) {
        $value = trim($value);
        $value = new FeedsEnclosure($value, file_get_mimetype($value));
      }
      else {
        return '';
      }
    }

    try {
      $file = $value->getFile($this->uploadDirectory, $this->configuration['existing']);
      return $file->id();
    }
    catch (Exception $e) {
      watchdog_exception('Feeds', $e, nl2br(check_plain($e)));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultConfiguration() {
    return array('existing' => FILE_EXISTS_RENAME);
  }

  /**
   * {@inheritdoc}
   *
   * @todo Inject $user.
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    $options = array(
      FILE_EXISTS_REPLACE => $this->t('Replace'),
      FILE_EXISTS_RENAME => $this->t('Rename'),
      FILE_EXISTS_ERROR => $this->t('Ignore'),
    );

    $form['existing'] = array(
      '#type' => 'select',
      '#title' => $this->t('Handle existing files'),
      '#options' => $options,
      '#default_value' => $this->configuration['existing'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    switch ($this->configuration['existing']) {
      case FILE_EXISTS_REPLACE:
        $message = 'Replace';
        break;

      case FILE_EXISTS_RENAME:
        $message = 'Rename';
        break;

      case FILE_EXISTS_ERROR:
        $message = 'Ignore';
        break;
    }

    return $this->t('Exsting files: %existing', array('%existing' => $message));
  }

}
