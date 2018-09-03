<?php

/**
 * @file
 * API documentation for the Address Field lookup module.
 */

/**
 * Defines an address field lookup service.
 *
 * @return array
 *   An associative array of address field lookup services, keyed by machine
 *   name. The array contains the following values:
 *     - 'name' A human readable name for the service
 *     - 'class' A string specifying the PHP class that implements the
 *       AddressFieldLookupInterface interface.
 *     - 'object factory' A function responsible for instantiating the PHP
 *       class defined above. Takes this config array and a country code as
 *       parameters.
 *     - 'description' A brief description of the address field lookup service.
 *     - 'config path' The path to the configuration form for this service. The
 *       path will be used as an inline link on the address field lookup module
 *       configuration form. Note that the module implementing the hook must
 *       also define the menu URL and callback.
 *     - 'test data' An example value that will be used to test the status of
 *       connectivity to the service.
 *
 * @see addressfield_lookup_services()
 * @see hook_addressfield_lookup_service_info_alter()
 */
function hook_addressfield_lookup_service_info() {
  return array(
    'my_awesome_postcode' => array(
      'name' => t('My awesome postcode API'),
      'class' => 'MyAwesomePostcodeAPI',
      'object factory' => 'my_awesome_postcode_create',
      'description' => t('Provides an address field lookup service based on integration with the My Awesome Postcode API.'),
      'config path' => 'admin/config/regional/addressfield-lookup/my-awesome-addressfield-lookup-service/configure',
      'test data' => 'BH15 1HH',
    ),
  );
}

/**
 * Alters the list of address field lookup services defined by other modules.
 *
 * @param array $addressfield_lookup_services
 *   The array of address field lookup services defined by other modules.
 *
 * @see hook_addressfield_lookup_service_info()
 */
function hook_addressfield_lookup_service_info_alter(array &$addressfield_lookup_services) {
  // Swap in a new REST class for the My Awesome Postcode API.
  $addressfield_lookup_services['my_awesome_postcode']['class'] = 'MyAwesomePostcodeRestAPI';
}

/**
 * Update/alter the addressfield format defined by addressfield_lookup.
 *
 * @param array $format
 *   The address format being generated.
 * @param array $address
 *   The address this format is generated for.
 *
 * @return array $format
 *   The address format with any changes made.
 *
 * @see addressfield_lookup_addressfield_format_generate
 * @see addressfield_lookup_get_format_updates
 */
function hook_addressfield_lookup_format_update(array $format, array $address) {
  // Re-order the premise element.
  $format['street_block']['premise']['#weight'] = -9;

  return $format;
}

/**
 * Update/alter the cache ID used during the get addresses phase.
 *
 * @param string $cache_id
 *   The cache ID used during the get addresses phase.
 * @param string $country
 *   ISO2 code of the country to get addresses in.
 *
 * @return string $cache_id
 *   The cache ID with any changes made.
 *
 * @see addressfield_lookup_get_addresses
 */
function hook_addressfield_lookup_get_addresses_cache_id_update($cache_id, $country) {
  $user = \Drupal::currentUser();

  // Append the current user ID to the cache ID.
  $cache_id .= ':' . $user->uid;

  return $cache_id;
}
