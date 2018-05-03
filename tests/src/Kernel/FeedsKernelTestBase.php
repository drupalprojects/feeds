<?php

namespace Drupal\Tests\feeds\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\feeds\Traits\FeedCreationTrait;
use Drupal\Tests\feeds\Traits\FeedsCommonTrait;
use Drupal\Tests\feeds\Traits\FeedsReflectionTrait;

/**
 * Provides a base class for Feeds kernel tests.
 */
abstract class FeedsKernelTestBase extends EntityKernelTestBase {

  use FeedCreationTrait;
  use FeedsCommonTrait;
  use FeedsReflectionTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['field', 'node', 'feeds', 'text', 'filter'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Install database schemes.
    $this->installEntitySchema('feeds_feed');
    $this->installEntitySchema('feeds_subscription');
    $this->installSchema('node', 'node_access');

    // Create a content type.
    $this->setUpNodeType();
  }

  /**
   * Installs body field (not needed for every kernel test).
   */
  protected function setUpBodyField() {
    $this->installConfig(['field', 'filter', 'node']);
    node_add_body_field($this->nodeType);
  }

  /**
   * Installs a file and image fields (not needed for every kernel test).
   */
  protected function setUpFileFields() {
    // Create a file field.
    $this->installModule('file');
    $this->installConfig(['field', 'node', 'file']);
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);

    $this->createFieldWithStorage('field_file', [
      'type' => 'file',
      'bundle' => 'article',
      'field' => [
        'settings' => ['file_extensions' => 'txt'],
      ],
    ]);

    // Create an image field.
    $this->installModule('image');
    $this->installConfig(['image']);

    $this->createFieldWithStorage('field_image', [
      'type' => 'image',
      'bundle' => 'article',
      'field' => [
        'settings' => ['file_extensions' => 'svg'],
      ],
    ]);
  }

}
