<?php

namespace Drupal\Tests\addressfield_lookup\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests module uninstallation.
 *
 * @group addressfield_lookup
 */
class UninstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['addressfield_lookup'];

  /**
   * Tests module uninstallation.
   */
  public function testUninstall() {
    // Confirm that Address Field Lookup has been installed.
    $module_handler = $this->container->get('module_handler');
    $this->assertTrue($module_handler->moduleExists('addressfield_lookup'));

    // Uninstall Address Field Lookup.
    $this->container->get('module_installer')->uninstall(['addressfield_lookup']);
    $this->assertFalse($module_handler->moduleExists('addressfield_lookup'));
  }

}
