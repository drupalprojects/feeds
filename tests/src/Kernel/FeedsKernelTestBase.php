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

}
