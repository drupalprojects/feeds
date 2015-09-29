<?php

/**
 * @file
 * Contains \Drupal\Tests\feeds\Unit\Feeds\Fetcher\UploadFetcherTest.
 */

namespace Drupal\Tests\feeds\Unit\Feeds\Fetcher;

use Drupal\Core\Form\FormState;
use Drupal\feeds\Feeds\Fetcher\UploadFetcher;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Fetcher\UploadFetcher
 * @group feeds
 */
class UploadFetcherTest extends FeedsUnitTestCase {

  protected $fileStorage;
  protected $fetcher;
  protected $state;

  public function setUp() {
    parent::setUp();

    $this->fileStorage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $entity_manager->expects($this->once())
      ->method('getStorage')
      ->with('file')
      ->will($this->returnValue($this->fileStorage));

    $this->fetcher = new UploadFetcher(
      ['feed_type' => $this->getMock('Drupal\feeds\FeedTypeInterface')],
      'test_plugin',
      ['plugin_type' => 'fetcher'],
      $this->getMock('Drupal\file\FileUsage\FileUsageInterface'),
      $entity_manager,
      $this->getMock('Drupal\Component\Uuid\UuidInterface'),
      $this->getMockStreamWrapperManager()
    );

    $this->fetcher->setStringTranslation($this->getStringTranslationStub());

    $this->state = $this->getMock('Drupal\feeds\StateInterface');
  }

  public function testFetch() {
    touch('vfs://feeds/test_file');

    $feed = $this->getMock('Drupal\feeds\FeedInterface');
    $feed->expects($this->any())
      ->method('getSource')
      ->will($this->returnValue('vfs://feeds/test_file'));
    $this->fetcher->fetch($feed, $this->state);
  }

  /**
   * @expectedException \RuntimeException
   */
  public function testFetchException() {
    $feed = $this->getMock('Drupal\feeds\FeedInterface');
    $feed->expects($this->any())
      ->method('getSource')
      ->will($this->returnValue('vfs://feeds/test_file'));
    $this->fetcher->fetch($feed, $this->state);
  }

  public function testBuildFeedForm() {
    $feed = $this->getMock('Drupal\feeds\FeedInterface');
    $feed->expects($this->any())
      ->method('getConfigurationFor')
      ->with($this->fetcher)
      ->will($this->returnValue($this->fetcher->sourceDefaults()));

    $form_state = new FormState();
    $form = $this->fetcher->buildFeedForm([], $form_state, $feed);

    $form_state->setValue('source', [10]);
    $this->fileStorage->expects($this->exactly(2))
      ->method('load')
      ->will($this->returnValue($this->getMock('Drupal\file\FileInterface')));
    $this->fetcher->submitFeedForm($form, $form_state, $feed);

    // Submit again.
    $feed = $this->getMock('Drupal\feeds\FeedInterface');
    $feed->expects($this->any())
      ->method('getConfigurationFor')
      ->with($this->fetcher)
      ->will($this->returnValue(['fid' => 10] + $this->fetcher->sourceDefaults()));
    $this->fetcher->submitFeedForm($form, $form_state, $feed);
  }

  public function testOnFeedDeleteMultiple() {
    $feed = $this->getMock('Drupal\feeds\FeedInterface');
    $feed->expects($this->exactly(2))
      ->method('getConfigurationFor')
      ->with($this->fetcher)
      ->will($this->returnValue(['fid' => 10] + $this->fetcher->sourceDefaults()));

    $feeds = [$feed, $feed];
    $this->fetcher->onFeedDeleteMultiple($feeds);
  }

  /**
   * Tests the configuration form.
   *
   * @todo We don't have any assertions here yet. Wait until file handling is
   *   objectified.
   */
  public function testBuildConfigurationForm() {
    $form_state = new FormState();
    $form = $this->fetcher->buildConfigurationForm([], $form_state);
    $form['directory']['#parents'] = ['directory'];

    // Validate.
    $form_state->setValues($this->fetcher->defaultConfiguration());
    $form_state->setValue(['directory'], 'vfs://feeds/uploads');
    $this->fetcher->validateConfigurationForm($form, $form_state);
    $this->assertSame(0, count($form_state->clearErrors()));

    // // Validate.
    $form_state->setValue(['directory'], 'vfs://noroot');
    $this->fetcher->validateConfigurationForm($form, $form_state);
    $this->assertSame('The chosen directory does not exist and attempts to create it failed.', (string) $form_state->getError($form['directory']));
  }

}

