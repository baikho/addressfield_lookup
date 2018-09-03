<?php

/**
 * @file
 * API documentation for the Address Field lookup module.
 */

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
