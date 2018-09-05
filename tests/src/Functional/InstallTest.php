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
   * Tests if the module is installable.
   */
  public function testInstallation() {
    $this->assertFalse($this->container->get('module_handler')->moduleExists('addressfield_lookup'), 'Module is not installed yet.');
    $this->assertTrue($this->container->get('module_installer')->install(['addressfield_lookup']), 'Installation successful.');
    // The module handler is reinstantiated after each module install, so
    // therefore it should no longer be obtained from $this->container.
    $this->assertTrue(\Drupal::service('module_handler')->moduleExists('addressfield_lookup'), 'Module is installed.');
  }

}
