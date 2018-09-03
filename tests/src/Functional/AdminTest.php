<?php

namespace Drupal\Tests\addressfield_lookup\Functional;

/**
 * Tests the administrative interface of the Address Field Lookup module.
 *
 * @group addressfield_lookup
 */
class AdminTest extends AddressFieldLookupBrowserTestBase {

  /**
   * Tests the address field lookup services overview page.
   *
   * Page found at /admin/config/regional/addressfield-lookup.
   */
  public function testAddressFieldLookupServicesPage() {
    // Check permissions and get page contents.
    $this->getWithPermissions(['administer addressfield lookup services'], 'admin/config/regional/addressfield-lookup');

    // Check if all expected services are displayed.
    $expected_services = [
      'example' => 'Example',
    ];
    foreach ($expected_services as $id => $label) {
      $this->assertSession()->pageTextContains($label);
      $this->assertSession()->pageTextContains('(Machine name: ' . $id . ')');
      $this->assertFieldByName('default_service', $id, "Radio button for $label is displayed.");
    }

    // Check for submit buttons.
    $this->assertFieldByName('op', t('Save configuration'), 'Save configuration button is displayed');
    $this->assertFieldByName('op', t('Test default service'), 'Test button is displayed');

    // Test the save configuration button and set the default service.
    $this->submitForm(['default_service' => 'example'], 'Save configuration');
    $this->assertSession()->pageTextContains('Configuration saved.');

    // Load the default service and test it is the example service.
    // @todo
    //$default_service = addressfield_lookup_get_default_service();
    //$this->assertEqual($default_service['name'], t('Example'));

    // Test the default service is working.
    $this->markTestIncomplete('testing default service is not implemented yet');
    $this->submitForm([], 'Test default service');
    $this->assertSession()->pageTextContains('The default service (Example) test was successful.', 'Default service tested and working.');
  }

  /**
   * Tests the address field lookup settings page.
   *
   * Page found at /admin/config/regional/addressfield-lookup/settings.
   */
  public function testAddressFieldLookupSettingsPage() {
    // Check permissions and get page contents.
    $this->getWithPermissions(['administer addressfield lookup services'], 'admin/config/regional/addressfield-lookup/settings');

    // Check if the UI elements are displayed.
    $this->assertFieldByName('addressfield_hide_extra_fields', TRUE, 'Hide extra fields checkbox is displayed');
    $this->assertFieldByName('cache_length', 3600, 'Cache length field is displayed');
  }

}
