<?php

namespace Drupal\addressfield_lookup_postcodeanywhere\Plugin\AddressLookup;

use Drupal\addressfield_lookup\AddressLookupInterface;
use Drupal\addressfield_lookup\Plugin\AddressLookup\AddressLookupBase;
use Drupal\addressfield_lookup_postcodeanywhere\PCAPredictAdapter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Address Field Lookup service for Postcode anywhere.
 *
 * @AddressLookup(
 *   id = "postcode_anywhere",
 *   label = @Translation("Postcode Anywhere"),
 *   description = @Translation("The Postcode Anywhere AddressLookup Provider"),
 *   route = "loqate.loqate_api_key_config_form",
 * )
 */
class AddressFieldLookupPostcodeAnywhere extends AddressLookupBase {

  /**
   * API Adapter for PCA Predict.
   *
   * @var \Drupal\addressfield_lookup_postcodeanywhere\PCAPredictAdapter
   *
   * @see PCAPredictAdapter
   * @see PCAPredict
   */
  protected $api;

  /**
   * The Id from a previous Find or FindByPosition.
   *
   * @var string
   */
  protected $lastId = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    return $instance->setAPi($container->get('addressfield_lookup_postcodeanywhere.pca_predict'));
  }

  /**
   * @param \Drupal\addressfield_lookup_postcodeanywhere\PCAPredictAdapter $api
   *   An instantiated API adapater.
   *
   * @return $this;
   */
  public function setAPi(PCAPredictAdapter $api) {
    $this->api = $api;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function lookup($term) {
    // Get the API result.
    $api_response = $this->api->find($term, $this->lastId);

    if (!is_array($api_response)) {
      return FALSE;
    }

    // Build the format we need.
    $results = [];

    foreach ($api_response as $api_response_item) {
      // Build the result array. Note that we are building a composite ID from
      // the actual Id value and the 'Next' operation.
      $result = [
        'id' => $api_response_item->Id . ':' . $api_response_item->Next,
        'street' => trim(preg_replace("/{$term}\,/si", '', $api_response_item->Text)),
        'place' => !empty($api_response_item->Description) ? '(' . $api_response_item->Description . ')' : '',
      ];

      $results[] = $result;
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressDetails($address_id) {
    // Get the API result.
    $api_response = $this->api->retrieve($address_id);

    if (!is_array($api_response)) {
      return FALSE;
    }

    // Address details array.
    $address_details = [
      'id' => $address_id,
    ];

    // Sub premise.
    if (!empty($api_response[0]->SubBuilding)) {
      $address_details['sub_premise'] = $api_response[0]->SubBuilding;
    }

    // Premise.
    $address_details['premise'] = '';
    $building_thoroughfare_component = 'BuildingNumber';

    if (!empty($api_response[0]->BuildingName)) {
      $address_details['premise'] = $api_response[0]->BuildingName;
    }
    elseif (!empty($api_response[0]->BuildingNumber)) {
      $address_details['premise'] = $api_response[0]->BuildingNumber;
      $building_thoroughfare_component = 'BuildingName';
    }

    // Parse the thoroughfare from the result array.
    $address_details['thoroughfare'] = !empty($api_response[0]) ? $this->api->getAddressThoroughfare($api_response[0], $building_thoroughfare_component) : '';

    // Locality.
    $address_details['locality'] = !empty($api_response[0]->City) ? $api_response[0]->City : '';

    // Dependent locality: Administrative area.
    if (!empty($api_response[0]->AdminAreaName) && $api_response[0]->AdminAreaName != $api_response[0]->City) {
      $address_details['dependent_locality'] = $api_response[0]->AdminAreaName;
    }

    // Postal code.
    $address_details['postal_code'] = !empty($api_response[0]->PostalCode) ? $api_response[0]->PostalCode : '';

    // Get the list of administrative areas for the current country.
    if (!empty($api_response[0]->CountryIso2)) {
      $administrative_areas = $this->getAdministrativeAreas($api_response[0]->CountryIso2);
    }

    // Determine the province name from the API result.
    if (!empty($api_response[0]->Province)) {
      $province = $api_response[0]->Province;
    }
    elseif (!empty($api_response[0]->ProvinceName)) {
      $province = $api_response[0]->ProvinceName;
    }

    // Administrative area.
    if (!empty($administrative_areas) && is_array($administrative_areas) && !empty($administrative_areas[$province])) {
      $address_details['administrative_area'] = $administrative_areas[$province];
    }
    else {
      $address_details['administrative_area'] = isset($province) ? $province : '';
    }

    // Organisation name.
    $address_details['organisation_name'] = !empty($api_response[0]->Company) ? $api_response[0]->Company : '';

    return $address_details;
  }

  /**
   * Set the last Id value.
   *
   * @param string $last_id
   *   String containing the The Id from a previous Find.
   *
   * @return AddressLookupInterface
   *   The called object.
   */
  public function setLastId($last_id) {
    $this->lastId = $last_id;
    return $this;
  }

  /**
   * Get the last Id value.
   *
   * @return string $last_id
   *   String containing the The Id from a previous Find.
   */
  public function getLastId() {
    return $this->lastId;
  }

  /**
   * Load the list of administrative areas and flip the id/names.
   *
   * @param string $country
   *   String containing the ISO2 code of the country.
   *
   * @return array
   *   Array of administrative areas in the format name => id.
   *
   * @see addressfield_get_administrative_areas
   */
  private function getAdministrativeAreas($country) {
    $administrative_areas = &drupal_static(__FUNCTION__);

    if (!isset($administrative_areas)) {
      // Load the administrative areas for this country.
      module_load_include('inc', 'addressfield', 'addressfield.administrative_areas');
      $administrative_areas = addressfield_get_administrative_areas($country);

      // If there is a valid list of administrative areas flip the ids/names.
      if (is_array($administrative_areas)) {
        $administrative_areas = array_flip($administrative_areas);
      }
    }

    return $administrative_areas;
  }

}
