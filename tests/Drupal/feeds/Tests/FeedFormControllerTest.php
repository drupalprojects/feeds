<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\FeedFormControllerTest.
 */

namespace Drupal\feeds\Tests {

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\DependencyInjection\Container;
use Drupal\Tests\UnitTestCase;
use Drupal\feeds\FeedFormController;
use Drupal\feeds\Tests\Feeds\Fetcher\MockFetcher;
use Drupal\feeds\Tests\Feeds\Parser\MockParser;
use Drupal\feeds\Tests\Feeds\Processor\MockProcessor;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers \Drupal\feeds\FeedFormController
 */
class FeedFormControllerTest extends UnitTestCase {


  public static function getInfo() {
    return array(
      'name' => 'Feeds: Feed form controller',
      'description' => 'Tests the feed form controller.',
      'group' => 'Feeds',
    );
  }

  public function setUp() {
    $this->request = Request::create('/');
    $this->account = $this->getMock('\Drupal\Core\Session\AccountInterface');
    $this->container = $this->getMock('\Symfony\Component\DependencyInjection\ContainerInterface');
    $this->manager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');
    $this->entityInfo = $this->getMock('\Drupal\Core\Entity\EntityTypeInterface');
    $this->manager->expects($this->any())
                  ->method('getDefinition')
                  ->will($this->returnValue($this->entityInfo));
    $this->linkGenerator = $this->getMock('\Drupal\Core\Utility\LinkGeneratorInterface');
    $this->map = array(
      array('current_user', Container::EXCEPTION_ON_INVALID_REFERENCE, $this->account),
      array('entity.manager', Container::EXCEPTION_ON_INVALID_REFERENCE, $this->manager),
      array('link_generator', Container::EXCEPTION_ON_INVALID_REFERENCE, $this->linkGenerator),
    );

    $this->container->expects($this->any())
                    ->method('get')
                    ->will($this->returnValueMap($this->map));

    $this->date = $this->getMockBuilder('\Drupal\Core\Datetime\Date')
                       ->disableOriginalConstructor()
                       ->getMock();

    \Drupal::setContainer($this->container);

    $this->entityManager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');
    $this->config = $this->getMockBuilder('\Drupal\Core\Config\Config')
                         ->disableOriginalConstructor()
                         ->getMock();
    $this->translation = $this->getStringTranslationStub();
    $this->controller = new FeedFormController($this->entityManager, $this->config, $this->date);
    $this->controller->setTranslationManager($this->translation);
    $this->controller->setRequest($this->request);
    $this->fetcher = new MockFetcher();
    $this->parser = new MockParser();
    $this->processor = new MockProcessor();

    $this->importer = $this->getMock('\Drupal\feeds\ImporterInterface');
    $this->importer->expects($this->any())
                   ->method('getPlugins')
                   ->will($this->returnValue(array('fetcher' => $this->fetcher, 'parser' => $this->parser, 'processor' => $this->processor)));

    $this->entityLanguage = $this->getMock('\Drupal\Core\Language\Language');
    $this->feed = $this->getMock('\Drupal\feeds\FeedInterface');
    $this->feed->expects($this->any())
               ->method('language')
               ->will($this->returnValue($this->entityLanguage));
    $this->feed->expects($this->any())
               ->method('getImporter')
               ->will($this->returnValue($this->importer));
    $this->feed->expects($this->any())
               ->method('getAuthor')
               ->will($this->returnValue($this->account));
    $this->feed->expects($this->any())
               ->method('entityInfo')
               ->will($this->returnValue($this->entityInfo));
    $this->controller->setEntity($this->feed);
  }

  public function testCreate() {
    // $factory = $this->getConfigFactoryStub(array('user.settings' => array('anonymous' => 'Anonymous')));
    // $map = array(
    //   array('entity.manager', Container::EXCEPTION_ON_INVALID_REFERENCE, $this->manager),
    //   array('config.factory', Container::EXCEPTION_ON_INVALID_REFERENCE, $factory),
    //   array('date', Container::EXCEPTION_ON_INVALID_REFERENCE, $this->date),
    // );

    // $this->container->expects($this->any())
    //                 ->method('get')
    //                 ->will($this->returnValueMap($map));
    // $controller = FeedFormController::create($this->container);
  }

  public function testForm() {
    $form_state = array('values' => array());
    $form = $this->controller->form(array(), $form_state);
  }

  public function testValidate() {
    $form_state = array('values' => array());
    $form = $this->controller->validate(array(), $form_state);
  }

  public function testSubmit() {
    $form_state = array('values' => array());
    $form = $this->controller->submit(array(), $form_state);
  }

  public function testSave() {
    $form_state = array('values' => array());
    $form = $this->controller->save(array(), $form_state);
  }

  public function testDelete() {
    $form_state = array('values' => array());
    $form = $this->controller->delete(array(), $form_state);

    $this->assertTrue(array_key_exists('feeds_feed', $form_state['redirect_route']['route_parameters']));

    $request = Request::create('/?destination=asdfasf');
    $this->controller->setRequest($request);
    $form_state = array('values' => array());
    $form = $this->controller->delete(array(), $form_state);
    $this->assertSame('asdfasf', $form_state['redirect_route']['options']['query']['destination']);
  }

}
}

namespace {
if (!function_exists('form_execute_handlers')) {
  function form_execute_handlers() {}
}
if (!function_exists('form_state_values_clean')) {
  function form_state_values_clean() {}
}
if (!function_exists('watchdog')) {
  function watchdog() {}
}
if (!function_exists('drupal_set_message')) {
  function drupal_set_message() {}
}
}
