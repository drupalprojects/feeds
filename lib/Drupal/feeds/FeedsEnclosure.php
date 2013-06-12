<?php

namespace Drupal\feeds;

/**
 * Enclosure element, can be part of the result array.
 */
class FeedsEnclosure extends FeedsElement {
  protected $mime_type;

  /**
   * Constructor, requires MIME type.
   *
   * @param $value
   *   A path to a local file or a URL to a remote document.
   * @param $mimetype
   *   The mime type of the resource.
   */
  public function __construct($value, $mime_type) {
    parent::__construct($value);
    $this->mime_type = $mime_type;
  }

  /**
   * @return
   *   MIME type of return value of getValue().
   */
  public function getMIMEType() {
    return $this->mime_type;
  }

  /**
   * Use this method instead of FeedsElement::getValue() when fetching the file
   * from the URL.
   *
   * @return
   *   Value with encoded space characters to safely fetch the file from the URL.
   *
   * @see FeedsElement::getValue()
   */
  public function getUrlEncodedValue() {
    return str_replace(' ', '%20', $this->getValue());
  }

  /**
   * Use this method instead of FeedsElement::getValue() to get the file name
   * transformed for better local saving (underscores instead of spaces)
   *
   * @return
   *   Value with space characters changed to underscores.
   *
   * @see FeedsElement::getValue()
   */
  public function getLocalValue() {
    return str_replace(' ', '_', $this->getValue());
  }

  /**
   * @return
   *   The content of the referenced resource.
   */
  public function getContent() {
    feeds_include_library('http_request.inc', 'http_request');
    $result = http_request_get($this->getUrlEncodedValue());
    if ($result->code != 200) {
      throw new Exception(t('Download of @url failed with code !code.', array('@url' => $this->getUrlEncodedValue(), '!code' => $result->code)));
    }
    return $result->data;
  }

  /**
   * Get a Drupal file object of the enclosed resource, download if necessary.
   *
   * @param $destination
   *   The path or uri specifying the target directory in which the file is
   *   expected. Don't use trailing slashes unless it's a streamwrapper scheme.
   *
   * @return
   *   A Drupal temporary file object of the enclosed resource.
   *
   * @throws Exception
   *   If file object could not be created.
   */
  public function getFile($destination) {

    if ($this->getValue()) {
      // Prepare destination directory.
      file_prepare_directory($destination, FILE_MODIFY_PERMISSIONS | FILE_CREATE_DIRECTORY);
      // Copy or save file depending on whether it is remote or local.
      if (drupal_realpath($this->getValue())) {
        $file = entity_create('file', array(
          'uid' => 0,
          'uri' => $this->getValue(),
          'filemime' => $this->mime_type,
          'filename' => basename($this->getValue()),
        ));
        if (dirname($file->uri) != $destination) {
          $file = file_copy($file, $destination);
        }
        else {
          // If file is not to be copied, check whether file already exists,
          // as file_save() won't do that for us (compare file_copy() and
          // file_save())
          $existing_files = file_load_multiple(array(), array('uri' => $file->uri));
          if (count($existing_files)) {
            $existing = reset($existing_files);
            $file->fid = $existing->fid;
            $file->filename = $existing->filename;
          }
          $file->save();
        }
      }
      else {
        $filename = basename($this->getLocalValue());
        if (module_exists('transliteration')) {
          require_once drupal_get_path('module', 'transliteration') . '/transliteration.inc';
          $filename = transliteration_clean_filename($filename);
        }
        if (file_uri_target($destination)) {
          $destination = trim($destination, '/') . '/';
        }
        try {
          $file = file_save_data($this->getContent(), $destination . $filename);
        }
        catch (Exception $e) {
          watchdog_exception('Feeds', $e, nl2br(check_plain($e)));
        }
      }

      // We couldn't make sense of this enclosure, throw an exception.
      if (!$file) {
        throw new Exception(t('Invalid enclosure %enclosure', array('%enclosure' => $this->getValue())));
      }
    }

    return $file;
  }

}
