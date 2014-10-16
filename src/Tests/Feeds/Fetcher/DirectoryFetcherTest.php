<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Feeds\Fetcher\DirectoryFetcherTest.
 */

namespace Drupal\feeds\Tests\Feeds\Fetcher;

use Drupal\Core\DependencyInjection\ContainerBuilder;
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
  protected $state;
  protected $feed;

  public function setUp() {
    parent::setUp();

    $importer = $this->getMock('Drupal\feeds\ImporterInterface');
    $container = new ContainerBuilder();
    $container->set('stream_wrapper_manager', $this->getMockStreamWrapperManager());
    $this->fetcher = new DirectoryFetcher(['importer' => $importer], 'directory', []);
    $this->fetcher->setStringTranslation($this->getStringTranslationStub());

    $this->state = new State();

    $this->feed = $this->getMock('Drupal\feeds\FeedInterface');
    $this->feed->expects($this->any())
      ->method('getSource')
      ->will($this->returnValue('vfs://feeds'));
    $this->feed->expects($this->any())
      ->method('getState')
      ->will($this->returnValue($this->state));

    // Prepare filesystem.
    touch('vfs://feeds/test_file_1.txt');
    touch('vfs://feeds/test_file_2.txt');
    touch('vfs://feeds/test_file_3.txt');
    touch('vfs://feeds/test_file_3.mp3');
    chmod('vfs://feeds/test_file_3.txt', 0333);
    mkdir('vfs://feeds/subdir');
    touch('vfs://feeds/subdir/test_file_4.txt');
    touch('vfs://feeds/subdir/test_file_4.mp3');
  }

  public function testFetchFile() {
    $feed = $this->getMock('Drupal\feeds\FeedInterface');
    $feed->expects($this->any())
      ->method('getSource')
      ->will($this->returnValue('vfs://feeds/test_file_1.txt'));
    $result = $this->fetcher->fetch($feed);
    $this->assertSame('vfs://feeds/test_file_1.txt', $result->getFilePath());
  }

  /**
   * @expectedException \RuntimeException
   */
  public function testFetchDir() {
    $result = $this->fetcher->fetch($this->feed);
    $this->assertSame($this->state->total, 2);
    $this->assertSame('vfs://feeds/test_file_1.txt', $result->getFilePath());
    $this->assertSame('vfs://feeds/test_file_2.txt', $this->fetcher->fetch($this->feed)->getFilePath());

    chmod('vfs://feeds', 0333);
    $result = $this->fetcher->fetch($this->feed);
  }

  public function testRecursiveFetchDir() {
    $this->fetcher->setConfiguration(['recursive_scan' => TRUE]);

    $result = $this->fetcher->fetch($this->feed);
    $this->assertSame($this->state->total, 3);
    $this->assertSame('vfs://feeds/test_file_1.txt', $result->getFilePath());
    $this->assertSame('vfs://feeds/test_file_2.txt', $this->fetcher->fetch($this->feed)->getFilePath());
    $this->assertSame('vfs://feeds/subdir/test_file_4.txt', $this->fetcher->fetch($this->feed)->getFilePath());
  }

  /**
   * @expectedException \Drupal\feeds\Exception\EmptyFeedException
   */
  public function testEmptyDirectory() {
    mkdir('vfs://feeds/emptydir');
    $feed = $this->getMock('Drupal\feeds\FeedInterface');
    $feed->expects($this->any())
      ->method('getSource')
      ->will($this->returnValue('vfs://feeds/emptydir'));
    $feed->expects($this->any())
      ->method('getState')
      ->will($this->returnValue($this->state));
    $result = $this->fetcher->fetch($feed);
  }

  public function testFeedForm() {
    $this->fetcher->setConfiguration(['allowed_schemes' => ['vfs']]);
    $this->feed->expects($this->any())
      ->method('getConfigurationFor')
      ->with($this->fetcher)
      ->will($this->returnValue($this->fetcher->sourceDefaults()));

    $form_state = new FormState();
    $form = $this->fetcher->buildFeedForm([], $form_state, $this->feed);
    $form['source']['#parents'] = ['source'];

    // Valid.
    $form_state->setValue(['source', 0, 'value'], 'vfs://feeds');
    $this->fetcher->validateFeedForm($form, $form_state, $this->feed);

    // Does not exist.
    $form_state->setValue(['source', 0, 'value'], 'vfs://doesnotexist');
    $this->fetcher->validateFeedForm($form, $form_state, $this->feed);
    $this->assertSame(count($form_state->getErrors()), 1);
  }

}
