<?php

namespace Drupal\addressfield_lookup;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Interface for the address_lookup plugin manager.
 */
interface AddressLookupManagerInterface extends PluginManagerInterface {

  /**
   * Returns the default address lookup service ID.
   *
   * @return string
   *   The default service plugin ID.
   */
  public function getDefaultId();

  /**
   * Returns the instantiated default address lookup service object.
   *
   * @param string $country
   *   ISO2 code of the country to get addresses in.
   *
   * @return \Drupal\addressfield_lookup\AddressLookupInterface|null
   *   The instantiated default addressfield lookup service object or NULL, if no default has been configured yet.
   */
  public function getDefault($country = NULL);

  /**
   * Performs an address lookup using the default address lookup service.
   *
   * @param string $search_term
   *   Search term to lookup addresses for.
   * @param string $country
   *   ISO2 code of the country to get addresses in.
   * @param bool $reset
   *   Force a reset of the addresses cache for this search term.
   *
   * @return array $addresses
   *   Array of search results in the format:
   *   - id: Address ID
   *   - street: Street (Address Line 1)
   *   - place: Remainder of address.
   */
  public function getAddresses($search_term, $country = NULL, $reset = FALSE);

  /**
   * Get the full details for an address using the default address lookup service.
   *
   * @param mixed $address_id
   *   ID of the address to get details for.
   * @param bool $reset
   *   Force a reset of the address details cache for this address ID.
   *
   * @return mixed $address_details
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
  public function getAddressDetails($address_id, $reset = FALSE);

  /**
   * Allow the default address lookup service to make alterations to the format.
   *
   * @param array $format
   *   The address format being generated.
   * @param array $address
   *   The address this format is generated for.
   */
  public function getFormatUpdates(array &$format, array $address);

}
