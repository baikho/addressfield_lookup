<?php

namespace Drupal\addressfield_lookup_example\Plugin\AddressLookup;

use Drupal\addressfield_lookup\Plugin\AddressLookup\AddressLookupBase;

/**
 * An example Address Field Lookup Service.
 *
 * @AddressLookup(
 *   id = "example",
 *   label = @Translation("Example"),
 *   description = @Translation("Provides an example address field lookup service."),
 *   test_data = "TS1 1ST",
 * )
 */
class Example extends AddressLookupBase {

  /**
   * A mock set of lookup results.
   *
   * @var array
   */
  protected $mockResults = [
    'TS1 1ST' => [
      'id' => 1234,
      'street' => 'Example Street',
      'place' => 'Example City',
    ],
  ];

  /**
   * A mock set of address details.
   *
   * @var array
   */
  protected $addressDetails = [
    1234 => [
      'id' => '1234',
      'sub_premise' => '',
      'premise' => '10',
      'thoroughfare' => 'Example Street',
      'dependent_locality' => '',
      'locality' => 'Example City',
      'postal_code' => 'TS1 1ST',
      'administrative_area' => 'Example State',
      'organisation_name' => '',
    ],
  ];

  /**
   * List of supported ISO2 country codes.
   *
   * @var array
   */
  protected $supportedCountries = [
    'GB',
    'FR',
  ];

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
    // No result.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressDetails($address_id) {
    // Check we have some address details for the ID.
    if (isset($this->addressDetails[$address_id]) && !empty($this->addressDetails[$address_id])) {
      return $this->addressDetails[$address_id];
    }
    // No result.
    return FALSE;
  }

}
