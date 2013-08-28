<?php

/**
 * @file
 * Contains \Drupal\feeds\FeedsEnclosure.
 *
 * @todo Unit test the crap out of this. It has caused much pain over the years.
 */

namespace Drupal\feeds;

use Drupal\Component\Utility\String;
use Drupal\feeds\Utility\HttpRequest;

/**
 * Enclosure element, can be part of the result array.
 */
class FeedsEnclosure extends FeedsElement {

  /**
   * The mimetype of this enclosure.
   *
   * @var string
   */
  protected $mimeType;

  /**
   * Constructs a FeedsEnclosure object.
   *
   * @param string $value
   *   A path to a local file or a URL to a remote document.
   * @param string $mime_type
   *   The mime type of the resource.
   */
  public function __construct($value, $mime_type) {
    parent::__construct($value);
    $this->mimeType = $mime_type;
  }

  /**
   * Returns the mimetype of the enclosure.
   *
   * @return string
   *   Mimetype of return value of getValue().
   */
  public function getMimeType() {
    return $this->mimeType;
  }

  /**
   * Returns a URL safe version of the file path.
   *
   * @return string
   *   Value with encoded space characters to safely fetch the file from the
   *   URL.
   *
   * @see FeedsElement::getValue()
   *
   * @todo What other characters can we encode? We can't just encode it because
   * it's most likely already encoded, just missing spaces. What if we decoded
   * it and then encoded it?
   */
  public function getUrlEncodedValue() {
    return str_replace(' ', '%20', $this->getValue());
  }

  /**
   * Returns a version of the value better suited for saving to the file system.
   *
   * @return string
   *   Value with space characters changed to underscores.
   *
   * @see FeedsElement::getValue()
   *
   * @todo Remove this. This is dumb.
   */
  public function getLocalValue() {
    return str_replace(' ', '_', $this->getValue());
  }

  /**
   * Returns the content of the enclosre as a string.
   *
   * @return string|false
   *   The content of the referenced resource, or false if the file could not be
   *   read. This should be checked with an ===, since an empty string could be
   *   returned.
   *
   * @throws \RuntimeException
   *   Thrown if the download failed.
   */
  public function getContent() {
    $http = new HttpRequest($this->getUrlEncodedValue());
    $result = $http->get();
    if ($result->code != 200) {
      throw new \RuntimeException(String::format('Download of @url failed with code !code.', array('@url' => $this->getUrlEncodedValue(), '!code' => $result->code)));
    }

    return file_get_contents($result->file);
  }

  /**
   * Get a Drupal file object of the enclosed resource, download if necessary.
   *
   * @param string $destination
   *   The path or uri specifying the target directory in which the file is
   *   expected. Don't use trailing slashes unless it's a streamwrapper scheme.
   *
   * @return \Drupal\file\Entity\File
   *   A Drupal temporary file object of the enclosed resource.
   *
   * @throws \RuntimeException
   *   If file object could not be created.
   *
   * @todo Refactor this
   */
  public function getFile($destination) {
    $file = FALSE;

    if ($this->getValue()) {
      // Prepare destination directory.
      file_prepare_directory($destination, FILE_MODIFY_PERMISSIONS | FILE_CREATE_DIRECTORY);
      // Copy or save file depending on whether it is remote or local.
      if (drupal_realpath($this->getValue())) {
        $file = entity_create('file', array(
          'uid' => 0,
          'uri' => $this->getValue(),
          'filemime' => $this->mimeType,
          'filename' => basename($this->getValue()),
        ));
        if (dirname($file->getFileUri()) != $destination) {
          $file = file_copy($file, $destination);
        }
        else {
          // If file is not to be copied, check whether file already exists,
          // as file_save() won't do that for us (compare file_copy() and
          // file_save())
          $existing_files = file_load_multiple(array(), array('uri' => $file->getFileUri()));
          if (count($existing_files)) {
            $existing = reset($existing_files);
            $file->fid = $existing->id();
            $file->setFilename($existing->getFilename());
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
        catch (\Exception $e) {
          watchdog_exception('Feeds', $e, nl2br(check_plain($e)));
        }
      }

      // We couldn't make sense of this enclosure, throw an exception.
      if (!$file) {
        throw new \RuntimeException(String::format('Invalid enclosure %enclosure', array('%enclosure' => $this->getValue())));
      }
    }

    return $file;
  }

}
