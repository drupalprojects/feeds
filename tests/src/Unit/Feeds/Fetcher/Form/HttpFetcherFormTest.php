<?php

/**
 * @file
 * Contains \Drupal\Tests\feeds\Unit\Feeds\Fetcher\Form\HttpFetcherFormTest.
 */

namespace Drupal\Tests\feeds\Unit\Feeds\Fetcher\Form;

use Drupal\Core\Form\FormState;
use Drupal\feeds\Feeds\Fetcher\Form\HttpFetcherForm;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Fetcher\Form\HttpFetcherForm
 * @group feeds
 */
class HttpFetcherFormTest extends FeedsUnitTestCase {

  public function test() {
    $form_object = new HttpFetcherForm($this->getMock('Drupal\feeds\Plugin\Type\FeedsPluginInterface'));
    $form_object->setStringTranslation($this->getStringTranslationStub());

    $form = $form_object->buildConfigurationForm([], new FormState());
    $this->assertSame(count($form), 4);
  }

}

