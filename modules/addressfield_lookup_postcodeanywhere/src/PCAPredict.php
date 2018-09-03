<?php

namespace Drupal\addressfield_lookup_postcodeanywhere;

/**
 * Abstract class for interacting with the PCA Predict API.
 */
abstract class PCAPredict {

  /**
   * Indicator for a find operation.
   *
   * Matches a possible return value for the 'Next' field from the CapturePlus
   * Interactive Find API endpoint.
   *
   * @var string
   *
   * @see http://www.pcapredict.com/support/webservice/captureplus/interactive/find/2.1/
   */
  const FIND_OPERATION = 'Find';

  /**
   * Indicator for retrieve operation.
   *
   * Matches a possible return value for the 'Next' field from the CapturePlus
   * Interactive Find API endpoint.
   *
   * @var string
   *
   * @see http://www.pcapredict.com/support/webservice/captureplus/interactive/find/2.1/
   */
  const RETRIEVE_OPERATION = 'Retrieve';

  /**
   * Root URL for the PCA Predict API.
   *
   * @var string
   */
  protected $root = 'services.postcodeanywhere.co.uk';

  /**
   * Format to use for the return data.
   *
   * @var string
   */
  protected $format = 'json';

  /**
   * Should API calls be made using https.
   *
   * @var bool
   */
  protected $https = TRUE;

  /**
   * The key to use to authenticate to the service.
   *
   * @var string
   */
  private $key;

  /**
   * The username associated with the Royal Mail license.
   *
   * Not required for click licenses.
   *
   * @var string
   */
  private $userName;

  /**
   * The language version of the address to return.
   *
   * @var string
   */
  protected $preferredLanguage;

  /**
   * The filter to apply to the output.
   *
   * @var string
   */
  protected $filter;

  /**
   * List of languages support by the PCA Predict API.
   *
   * @var array
   */
  protected $allowedLanguages = array('English', 'Welsh');

  /**
   * List of filters support by the PCA Predict API.
   *
   * @var array
   */
  protected $allowedFilters = array(
    'Everything',
    'PostalCodes',
    'Companies',
    'Places',
  );

  /**
   * List of valid return formats for the PCA Predict API.
   *
   * @var array
   */
  protected $validFormats = array('xmla', 'json');

  /**
   * The ISO2 code of the country to search in.
   *
   * @var string
   */
  protected $country;

  /**
   * Constructor.
   *
   * @param string $key
   *   The key to use to authenticate to the service.
   * @param string $user_name
   *   The username associated with the Royal Mail license
   *   (not required for click licenses).
   * @param string $preferred_language
   *   The language version of the address to return.
   * @param string $filter
   *   The filter to apply to the output.
   * @param string $country
   *   ISO2 code of the country to search in.
   */
  public function __construct($key, $user_name, $preferred_language, $filter = 'Everything', $country = 'GB') {
    // Set API credentials.
    $this->key = $key;
    $this->userName = $user_name;

    // Check the language is valid.
    if (in_array($preferred_language, $this->allowedLanguages)) {
      $this->preferredLanguage = $preferred_language;
    }
    else {
      // Otherwise default to English.
      $this->preferredLanguage = 'English';
    }

    // Check the filter is valid.
    if (in_array($filter, $this->allowedFilters)) {
      $this->filter = $filter;
    }
    else {
      // Not a valid filter.
      throw new Exception('Requested filter not supported by PCA Predict API.');
    }

    // Set the country to search in.
    $this->country = $country;
  }

  /**
   * Set the data return format.
   *
   * @param string $format
   *   String containing the required format.
   *
   * @return AddressFieldLookupInterface
   *   The called object.
   */
  public function setFormat($format) {
    // Check the requested format is valid.
    if (in_array($format, $this->validFormats)) {
      $this->format = $format;
    }
    else {
      // Not a valid format.
      throw new Exception('Requested data format not supported by PCA Predict API.');
    }

    return $this;
  }

  /**
   * Set the data filter.
   *
   * @param string $filter
   *   String containing the required filter.
   *
   * @return AddressFieldLookupInterface
   *   The called object.
   */
  public function setFilter($filter) {
    // Check the filter is valid.
    if (in_array($filter, $this->allowedFilters)) {
      $this->filter = $filter;
    }
    else {
      // Not a valid filter.
      throw new Exception('Requested filter not supported by PCA Predict API.');
    }

    return $this;
  }

  /**
   * Set the flag to indicate if API calls be made using https.
   *
   * @param bool $https
   *   Should API calls be made using https.
   *
   * @return AddressFieldLookupInterface
   *   The called object.
   */
  public function setHttps($https) {
    // Check we have an endpoint for the requested format.
    if (is_bool($https)) {
      $this->https = $https;
    }
    else {
      throw new Exception('HTTPS flag must be a boolean.');
    }

    return $this;
  }

  /**
   * Call the API endpoint and return the result.
   *
   * @param string $endpoint
   *   API endpoint to call.
   * @param array $params
   *   Array of parameters to pass to the API.
   *
   * @return string $api_response
   *   Raw API response.
   */
  protected function callApi($endpoint, $params = array()) {
    // Array parameters that we need on every request.
    $default_params = array(
      'Key' => $this->key,
      'PreferredLanguage' => $this->preferredLanguage,
      'UserName' => $this->userName,
    );

    $params += $default_params;

    // Build the API url.
    $api_url = $this->buildApiUrl($endpoint, $params);

    // Call the API.
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    // Get the API response.
    $api_response = curl_exec($ch);

    // Get the response code.
    $api_response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Throw an exception if the repsonse code was not 200.
    if ($api_response_code != 200) {
      throw new Exception('Could not reach the PCA Predict API.');
    }

    return $api_response;
  }

  /**
   * Build the URL required for the API call.
   *
   * @param string $endpoint
   *   API endpoint to call.
   * @param array $params
   *   Array of parameters to pass to the API.
   *
   * @return string $api_url
   *   URL for the API call.
   */
  protected function buildApiUrl($endpoint, $params = array()) {
    // Build the URL string.
    $api_url = $this->https ? 'https' : 'http';

    // Determine the endpoint to use based on the format.
    if (in_array($this->format, array_keys($this->validFormats))) {
      $api_url .= '://' . $this->root . '/' . $endpoint . '/' . $this->format . '.ws';
    }
    else {
      throw new Exception('Requested data format not supported by PCA Predict API.');
    }

    // Build the query string.
    if (!empty($params)) {
      $api_url .= '?' . http_build_query($params, '', '&');
    }

    return $api_url;
  }

  /**
   * Parse the response from the API based on the required format.
   *
   * @param string $api_response
   *   The raw resonse from the API.
   *
   * @return array $parsed_api_response
   *   Array containing the API response items in the requested format.
   */
  protected function parseApiResponse($api_response) {
    // Check we have some data to parse.
    if (empty($api_response)) {
      return FALSE;
    }

    // Array for the parsed response.
    $parsed_api_response = array();

    // Parse based on the requested format.
    switch ($this->format) {
      case 'json':
        // Decode the json.
        $parsed_api_response = json_decode($api_response);

        // Check for any errors.
        if (isset($parsed_api_response[0]->Error)) {
          throw new Exception('Error ' . $parsed_api_response[0]->Error . ' (' . $parsed_api_response[0]->Description . '). ' . $parsed_api_response[0]->Cause . '. Resolution: ' . $parsed_api_response[0]->Resolution);
        }

        break;

      case 'xml':
        // Decode the XML.
        $api_xml_response = simplexml_load_string($api_response);

        // Check for any errors.
        if ($api_xml_response->Columns->Column->attributes()->Name == "Error") {
          throw new Exception('Error ' . $api_xml_response->Rows->Row->attributes()->Error . ' (' . $api_xml_response->Rows->Row->attributes()->Description . '). ' . $api_xml_response->Rows->Row->attributes()->Cause . '. Resolution: ' . $api_xml_response->Rows->Row->attributes()->Resolution);
        }

        // Parse the XML.
        if (!empty($api_xml_response->Rows)) {
          $parsed_api_response = array();

          $row_count = 0;

          // Loop through each XML row.
          foreach ($api_xml_response->Rows->Row as $api_xml_row) {
            // Initialize a new object for this item.
            $parsed_api_response[$row_count] = new stdClass();

            // Loop through each XML column.
            foreach ($api_xml_response->Columns->Column as $api_xml_column) {
              // Get the column name.
              $xml_column_name = $api_xml_column->attributes()->Name;

              // Use the column name to set the relevant field from the row on
              // our parsed API object array.
              $parsed_api_response[$row_count]->{$xml_column_name} = $api_xml_row->attributes()->{$xml_column_name}->__toString();
            }

            $row_count++;
          }
        }

        break;
    }

    return is_array($parsed_api_response) ? $parsed_api_response : FALSE;
  }

  /**
   * Sort find lookup results by their 'Next' value.
   *
   * Anything with a 'Find' value needs to be moved higher up the list.
   *
   * Callback for usort.
   */
  protected function findResultSort($a, $b) {
    if ($a->Next == $b->Next) {
      return 0;
    }
    elseif ($a->Next === PCAPredict::FIND_OPERATION && $b->Next === PCAPredict::RETRIEVE_OPERATION) {
      return -1;
    }
    else {
      return 1;
    }
  }

}
