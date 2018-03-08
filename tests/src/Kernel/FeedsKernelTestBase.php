<?php

namespace Drupal\Tests\feeds\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\NodeType;
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
   * The node type to test with.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $nodeType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Install database schemes.
    $this->installEntitySchema('feeds_feed');
    $this->installEntitySchema('feeds_subscription');
    $this->installSchema('node', 'node_access');

    // Create a content type with a body field.
    $this->nodeType = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $this->nodeType->save();
  }

  /**
   * Installs body field (not needed for every kernel test).
   */
  protected function setUpBodyField() {
    $this->installConfig(['field', 'filter', 'node']);
    node_add_body_field($this->nodeType);
  }

}
