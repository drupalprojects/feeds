<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Feeds\Fetcher\Form\HttpFetcherFormTest.
 */

namespace Drupal\feeds\Tests\Feeds\Fetcher\Form;

use Drupal\Core\Form\FormState;
use Drupal\feeds\Feeds\Fetcher\Form\HttpFetcherForm;
use Drupal\feeds\Tests\FeedsUnitTestCase;

/**
 * @covers \Drupal\feeds\Feeds\Fetcher\Form\HttpFetcherForm
 * @group Feeds
 */
class HttpFetcherFormTest extends FeedsUnitTestCase {

  public function test() {
    $form_object = new HttpFetcherForm($this->getMock('Drupal\feeds\Plugin\Type\FeedsPluginInterface'));
    $form_object->setStringTranslation($this->getStringTranslationStub());

    $form = $form_object->buildConfigurationForm([], new FormState());
    $this->assertSame(count($form), 4);
  }

}

