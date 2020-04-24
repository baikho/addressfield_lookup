<?php

namespace Drupal\addressfield_lookup_postcodeanywhere;

/**
 * Adapter for the PCAPredict API.
 */
class PCAPredictAdapter extends PCAPredict {

  /**
   * List of PCA Predict API endpoints. Keyed by their name.
   *
   * @var array
   *
   * @see http://www.pcapredict.com/support/webservices
   */
  private $endpoints = [
    'Find' => 'CapturePlus/Interactive/Find/v2.10',
    'Retrieve' => 'CapturePlus/Interactive/Retrieve/v2.10',
    'CountryData' => 'Extras/Lists/CountryData/v3.00',
  ];

  /**
   * Find addresses matching the search term.
   *
   * @param string $term
   *   The search term.
   * @param string $lastId
   *   The Id from a previous Find or FindByPosition.
   *
   * @return mixed
   *   Array of addresses or FALSE;
   */
  public function find($term, $lastId = NULL) {
    // Build array of parameters to pass to the API.
    $params = [
      'SearchTerm' => $term,
      'SearchFor' => $this->filter,
      'Country' => $this->country,
    ];

    // Add the last ID param if present.
    if (!is_null($lastId)) {
      $params['LastId'] = $lastId;
    }

    // Get the raw API result.
    $api_response = $this->callApi($this->endpoints[PCAPredict::FIND_OPERATION], $params);

    // Bail out if there was no response.
    if (!isset($api_response)) {
      return FALSE;
    }

    // Parse the API repsonse.
    $api_response = $this->parseApiResponse($api_response);

    // Sort the results to show any with a 'Find' next value first.
    usort($api_response, [$this, 'findResultSort']);

    return $api_response;
  }

  /**
   * Returns the full address details based on the Id.
   *
   * @param string $id
   *   The ID of the address.
   *
   * @return mixed
   *   The address details or FALSE.
   */
  public function retrieve($id) {
    // The first part of the ID is the actual address ID. The second part is the
    // 'Next' operation returned by the API.
    //
    // @see http://www.pcapredict.com/support/webservice/captureplus/interactive/find/2.1/
    $id_parts = explode(':', $id);

    // Get the raw API result.
    $api_response = $this->callApi($this->endpoints[PCAPredict::RETRIEVE_OPERATION], ['Id' => $id_parts[0]]);

    // Bail out if there was no response.
    if (!isset($api_response)) {
      return FALSE;
    }

    // Parse the API response.
    return $this->parseApiResponse($api_response);
  }

  /**
   * Returns the suppported country data.
   *
   * @return mixed
   *   The set of supported country data or FALSE.
   */
  public function getCountryData() {
    // Get the raw API result.
    $api_response = $this->callApi($this->endpoints['CountryData']);

    // Bail out if there was no response.
    if (!isset($api_response)) {
      return FALSE;
    }

    // Parse the API response.
    return $this->parseApiResponse($api_response);
  }

  /**
   * Parses an address and returns the thoroughfare value.
   *
   * The PCAPredict API repsonse is not consistent for thoroughfare value, it
   * varies depending on the country being searched in.
   *
   * @param object $address
   *   Address details from the PCAPredict retrieve API.
   * @param string $building_thoroughfare_component
   *   Name of an address result field, related to the Building, which should
   *   be used as part of the thoroughfare.
   *
   * @return string
   *   The thoroughfare from the address.
   *
   * @see http://www.pcapredict.com/support/webservice/captureplus/interactive/retrieve/2.1/
   */
  public function getAddressThoroughfare($address, $building_thoroughfare_component = 'BuildingNumber') {
    // First attempt to use the street values.
    $thoroughfare = array_filter([
      $address->{$building_thoroughfare_component},
      $address->Street,
      $address->SecondaryStreet,
    ]);

    // If we don't have any values, fall back and try to use the LineX values.
    if (empty($thoroughfare)) {
      $thoroughfare = array_filter([
        $address->Line1,
        $address->Line2,
      ]);
    }

    return implode(' ', $thoroughfare);
  }

}
