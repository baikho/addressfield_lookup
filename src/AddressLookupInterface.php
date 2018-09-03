<?php

namespace Drupal\addressfield_lookup;

/**
 * Interface that all Address Field lookup services need to implement.
 */
interface AddressLookupInterface {

  /**
   * Lookup addresses for the given search term.
   *
   * @param string $term
   *   String containing the lookup term.
   *
   * @return mixed $results
   *   Array of lookup results in the format:
   *     id - Address ID.
   *     street - Street (Address Line 1).
   *     place - Remainder of address.
   *
   *   Or FALSE.
   */
  public function lookup($term);

  /**
   * Get the full details for a given address.
   *
   * @param mixed $address_id
   *   ID of the address to get details for.
   *
   * @return mixed $address
   *   Array of details for the given address in the format:
   *     id - Address ID
   *     sub_premise - The sub_premise of this address
   *     premise - The premise of this address. (i.e. Apartment / Suite number).
   *     thoroughfare - The thoroughfare of this address. (i.e. Street address).
   *     dependent_locality - The dependent locality of this address.
   *     locality - The locality of this address. (i.e. City).
   *     postal_code - The postal code of this address.
   *     administrative_area - The administrative area of this address.
   *     (i.e. State/Province)
   *     organisation_name - Contents of a primary OrganisationName element
   *     in the xNL XML.
   *
   *   Or FALSE.
   */
  public function getAddressDetails($address_id);

}
