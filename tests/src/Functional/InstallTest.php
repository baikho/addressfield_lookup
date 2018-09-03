<?php

namespace Drupal\Tests\addressfield_lookup\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests module installation.
 *
 * @group addressfield_lookup
 */
class InstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [];

  /**
   * Module handler to ensure installed modules.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  public $moduleHandler;

  /**
   * Module installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  public $moduleInstaller;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->moduleHandler = $this->container->get('module_handler');
    $this->moduleInstaller = $this->container->get('module_installer');
  }

  /**
   * Tests module is installable.
   */
  public function testInstallation() {
    $this->assertFalse($this->moduleHandler->moduleExists('addressfield_lookup'), 'Module is not installed yet.');
    $this->assertTrue($this->moduleInstaller->install(['addressfield_lookup']), 'Installation successful.');
    $this->assertTrue($this->moduleHandler->moduleExists('addressfield_lookup'), 'Module is installed.');
  }

}
