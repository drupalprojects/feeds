<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\ModuleInstallUninstallWebTest.
 */

namespace Drupal\feeds\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests module installation and uninstallation.
 *
 * @group Feeds
 */
class ModuleInstallUninstallWebTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('feeds');

  /**
   * Test installation and uninstallation.
   */
  protected function testInstallationAndUninstallation() {
    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
    $module_installer = \Drupal::service('module_installer');
    $module_handler = \Drupal::moduleHandler();
    $this->assertTrue($module_handler->moduleExists('feeds'));

    // @todo Test default configuration.

    $module_installer->uninstall(array('feeds'));
    $this->assertFalse($module_handler->moduleExists('feeds'));
  }
}
