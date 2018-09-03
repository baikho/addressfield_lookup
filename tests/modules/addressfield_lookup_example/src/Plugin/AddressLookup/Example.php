<?php

namespace Drupal\addressfield_lookup_example\Plugin\AddressLookup;

use Drupal\addressfield_lookup\AddressLookupInterface;

/**
 * An example Adddress Field Lookup Service.
 *
 * @AddressLookup(
 *   id = "example",
 *   label = @Translation("Example"),
 *   description = @Translation("Provides an example address field lookup service."),
 *   factory = "addressfield_lookup_example_create",
 *   test_data = "TS1 1ST",
 * )
 */
class Example implements AddressLookupInterface {

  /**
   * A mock set of lookup results.
   *
   * @var array
   */
  protected $mockResults = array(
    'TS1 1ST' => array(
      'id' => 1234,
      'street' => 'Example Street',
      'place' => 'Example City',
    ),
  );

  /**
   * A mock set of address details.
   *
   * @var array
   */
  protected $addressDetails = array(
    1234 => array(
      'id' => '1234',
      'sub_premise' => '',
      'premise' => '10',
      'thoroughfare' => 'Example Street',
      'dependent_locality' => '',
      'locality' => 'Example City',
      'postal_code' => 'TS1 1ST',
      'administrative_area' => 'Example State',
      'organisation_name' => '',
    ),
  );

  /**
   * ISO2 country code.
   *
   * Defaults to UK.
   *
   * @var string
   */
  protected $country = 'GB';

  /**
   * List of supported ISO2 country codes.
   *
   * @var array
   */
  protected $supportedCountries = array(
    'GB',
    'FR',
  );

  /**
   * Set the country code for the lookup.
   *
   * @param string $country
   *   ISO2 country code.
   */
  public function setCountry($country) {
    $this->country = $country;
  }

  /**
   * {@inheritdoc}
   */
  public function lookup($term) {
    // Ensure the specified country code is valid.
    if (!in_array($this->country, $this->supportedCountries)) {
      return FALSE;
    }

    // Check for a valid search term in the mock results.
    if (isset($this->mockResults[$term]) && !empty($this->mockResults[$term])) {
      return array($this->mockResults[$term]);
    }
    else {
      // No result.
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressDetails($address_id) {
    // Check we have some address details for the ID.
    if (isset($this->addressDetails[$address_id]) && !empty($this->addressDetails[$address_id])) {
      return $this->addressDetails[$address_id];
    }
    else {
      // No result.
      return FALSE;
    }
  }

}
