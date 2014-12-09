<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Feeds\Fetcher\Form\DirectoryFetcherFormTest.
 */

namespace Drupal\feeds\Tests\Feeds\Fetcher\Form;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\feeds\Feeds\Fetcher\DirectoryFetcher;
use Drupal\feeds\Feeds\Fetcher\Form\DirectoryFetcherForm;
use Drupal\feeds\Tests\FeedsUnitTestCase;

/**
 * @covers \Drupal\feeds\Feeds\Fetcher\Form\DirectoryFetcherForm
 * @group Feeds
 */
class DirectoryFetcherFormTest extends FeedsUnitTestCase {


  public function testConfigurationForm() {
    $form_state = (new FormState())->setValues([
      'allowed_schemes' => ['public'],
      'allowed_extensions' => ' txt  pdf',
    ]);
    $container = new ContainerBuilder();
    $container->set('stream_wrapper_manager', $this->getMockStreamWrapperManager());

    $form_object = DirectoryFetcherForm::create($container, new DirectoryFetcher(['feed_type' => ''], '', []));
    $form_object->setStringTranslation($this->getStringTranslationStub());
    $form = $form_object->buildConfigurationForm([], $form_state);
    $form_object->validateConfigurationForm($form, $form_state);

    $this->assertSame(['txt', 'pdf'], $form_state->getValue(['allowed_extensions']));
  }

}

