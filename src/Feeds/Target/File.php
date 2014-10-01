<?php

/**
 * @file
 * Contains \Drupal\feeds\Feeds\Target\File.
 */

namespace Drupal\feeds\Feeds\Target;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Exception\TargetValidationException;
use Drupal\feeds\FieldTargetDefinition;

/**
 * Defines a file field mapper.
 *
 * @Plugin(
 *   id = "file",
 *   field_types = {"file"}
 * )
 */
class File extends EntityReference {

  /**
   * The file upload directory.
   *
   * @var string
   */
  protected $uploadDirectory;

  protected $fileExtensions;

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    return FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('target_id')
      ->addProperty('description');
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $settings, $plugin_id, array $plugin_definition) {
    parent::__construct($settings, $plugin_id, $plugin_definition);
    $this->fileExtensions = array_filter(explode(' ', $this->settings['file_extensions']));
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    foreach ($values as $column => $value) {
      switch ($column) {
        case 'description':
          $values[$column] = (string) $value;
          break;

        case 'target_id':
          $values[$column] = $this->getFile($value);
          break;
      }
    }

    $values['display'] = (int) $this->settings['display_default'];
  }

  /**
   * Returns a file id given a url.
   *
   * @param string $value
   *   A URL file object.
   *
   * @return int
   *   The file id.
   */
  protected function getFile($value) {
    // Prepare destination directory.
    $destination = $this->settings['uri_scheme'] . '://' . trim($this->settings['file_directory'], '/');
    file_prepare_directory($destination, FILE_MODIFY_PERMISSIONS | FILE_CREATE_DIRECTORY);
    $filepath = $destination . '/' . $this->getFileName($value);

    switch ($this->configuration['existing']) {
      case FILE_EXISTS_ERROR:
        if (file_exists($filepath) && $fid = $this->findEntity($filepath, 'uri')) {
          return $fid;
        }
        if ($file = file_save_data($this->getContent($value), $filepath, FILE_EXISTS_REPLACE)) {
          return $file->id();
        }
        break;

      default:
        if ($file = file_save_data($this->getContent($value), $filepath, $this->configuration['existing'])) {
          return $file->id();
        }
    }

    // Something bad happened while trying to save the file to the database. We
    // need to throw an exception so that we don't save an incomplete field
    // value.
    throw new TargetValidationException('There was an error saving the file: %file', ['%file' => $filepath]);
  }

  protected function getFileName($url) {
    $filename = trim(drupal_basename($url), " \t\n\r\0\x0B.");
    $extension = substr($filename, strrpos($filename, '.') + 1);

    if (!in_array($extension, $this->fileExtensions)) {
      throw new TargetValidationException('The file, %url, failed to save because the extension, %ext, is invalid.', ['%url' => $url, '%ext' => $extension]);
    }

    return $filename;
  }

  protected function getContent($url) {
    $response = \Drupal::httpClient()->get($url);

    if ($response->getStatusCode() !== '200') {
      $args = array(
        '%url' => $url,
        '@code' => $response->getStatusCode(),
      );
      throw new TargetValidationException('Download of %url failed with code @code.', $args);
    }

    return (string) $response->getBody();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array('existing' => FILE_EXISTS_ERROR) + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   *
   * @todo Inject $user.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
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
    $summary = parent::getSummary();

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

    return $summary . '<br>' . $this->t('Exsting files: %existing', array('%existing' => $message));
  }

}
