<?php

namespace Drupal\Tests\feeds\Kernel\Feeds\Target;

use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\feeds\Kernel\FeedsKernelTestBase;

/**
 * Tests that feed type declares dependencies on fields used as target.
 *
 * - The field dependencies must be listed in the feed type config file.
 * - When deleting a field, the feed type must be updated.
 *
 * @group feeds
 */
class FieldTargetDependencyTest extends FeedsKernelTestBase {

  /**
   * Tests dependency on a single field.
   */
  public function testFieldDependency() {
    // Add a field to the article content type.
    $this->createFieldWithStorage('field_alpha');

    // Create a feed type that maps to that field.
    $feed_type = $this->createFeedType([
      'mappings' => array_merge($this->getDefaultMappings(), [
        [
          'target' => 'field_alpha',
          'map' => ['value' => 'title'],
        ],
      ]),
    ]);

    // Assert that the field is listed as dependency.
    $dependencies = $feed_type->getDependencies();
    $expected = [
      'field.field.node.article.feeds_item',
      'field.field.node.article.field_alpha',
    ];
    $this->assertEquals($expected, $dependencies['config']);

    // Now delete the field.
    FieldConfig::loadByName('node', 'article', 'field_alpha')
      ->delete();

    // Assert that the feed type mappings were updated.
    $feed_type = $this->reloadEntity($feed_type);
    $this->assertEquals($this->getDefaultMappings(), $feed_type->getMappings());
  }

}
