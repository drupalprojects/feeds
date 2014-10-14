<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Feeds\Fetcher\DirectoryFetcherTest.
 */

namespace Drupal\feeds\Tests\Feeds\Fetcher;

use Drupal\Core\Form\FormState;
use Drupal\feeds\Feeds\Fetcher\DirectoryFetcher;
use Drupal\feeds\State;
use Drupal\feeds\Tests\FeedsUnitTestCase;

/**
 * @covers \Drupal\feeds\Feeds\Fetcher\DirectoryFetcher
 * @group Feeds
 */
class DirectoryFetcherTest extends FeedsUnitTestCase {

  protected $fetcher;

  public function setUp() {
    parent::setUp();

    $importer = $this->getMock('Drupal\feeds\ImporterInterface');
    $this->fetcher = new DirectoryFetcher(['importer' => $importer], 'directory', []);
    $this->fetcher->setStringTranslation($this->getStringTranslationStub());
  }

  public function testFetchFile() {
    touch('vfs://feeds/test_file');
    $feed = $this->getMock('Drupal\feeds\FeedInterface');
    $feed->expects($this->any())
      ->method('getSource')
      ->will($this->returnValue('vfs://feeds/test_file'));
    $result = $this->fetcher->fetch($feed);
    $this->assertSame('vfs://feeds/test_file', $result->getFilePath());
  }

  /**
   * @expectedException \RuntimeException
   */
  public function testFetchDir() {
    touch('vfs://feeds/test_file_1');
    touch('vfs://feeds/test_file_2');

    $state = new State();
    $feed = $this->getMock('Drupal\feeds\FeedInterface');
    $feed->expects($this->any())
      ->method('getSource')
      ->will($this->returnValue('vfs://feeds'));
    $feed->expects($this->any())
      ->method('getState')
      ->will($this->returnValue($state));

    $result = $this->fetcher->fetch($feed);
    $this->assertSame($state->total, 2);
    $this->assertSame('vfs://feeds/test_file_1', $result->getFilePath());

    // Fetch again.
    $result = $this->fetcher->fetch($feed);
    $this->assertSame('vfs://feeds/test_file_2', $result->getFilePath());

    // Throws an exception.
    $this->fetcher->fetch($feed);
  }

  public function testConfigurationForm() {
    $form_state = new FormState();
    $form_state->setValue(['fetcher_configuration'], ['allowed_schemes' => ['public'], 'allowed_extensions' => ' txt  pdf']);
    $form = $this->fetcher->buildConfigurationForm([], $form_state);
    $this->fetcher->validateConfigurationForm($form, $form_state);

    $allowed_extension = $form_state->getValue(['fetcher_configuration', 'allowed_extensions']);
    $this->assertSame(['txt', 'pdf'], $allowed_extension);
  }

  public function testFeedForm() {
    $feed = $this->getMock('Drupal\feeds\FeedInterface');
    $feed->expects($this->any())
      ->method('getConfigurationFor')
      ->with($this->fetcher)
      ->will($this->returnValue($this->fetcher->sourceDefaults()));

    $form_state = new FormState();
    $form = $this->fetcher->buildFeedForm([], $form_state, $feed);
    $form['source']['#parents'] = ['source'];

    // Valid.
    $form_state->setValue(['source', 0, 'value'], 'vfs://feeds');
    $this->fetcher->validateFeedForm($form, $form_state, $feed);

    // Invalid.
    $form_state->setValue(['source', 0, 'value'], 'badscheme://feeds');
    $this->fetcher->validateFeedForm($form, $form_state, $feed);
    $this->assertSame(count($form_state->getErrors()), 1);
    $form_state->clearErrors();

    // Does not exist.
    $form_state->setValue(['source', 0, 'value'], 'vfs://doesnotexist');
    $this->fetcher->validateFeedForm($form, $form_state, $feed);
    $this->assertSame(count($form_state->getErrors()), 1);
  }

}
