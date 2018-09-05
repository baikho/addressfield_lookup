<?php

namespace Drupal\addressfield_lookup;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages address lookup plugins.
 */
class AddressLookupManager extends DefaultPluginManager implements AddressLookupManagerInterface {

  /**
   * Constructs a new AddressLookupManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/AddressLookup', $namespaces, $module_handler, 'Drupal\addressfield_lookup\AddressLookupInterface', 'Drupal\addressfield_lookup\Annotation\AddressLookup');

    $this->alterInfo('addressfield_lookup_service_info');
    $this->setCacheBackend($cache_backend, 'addressfield_lookup_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultId() {
    $default_service_name = \Drupal::config('addressfield_lookup.settings')->get('default_service');

    // If there is no default set, assume the only service available is default.
    if (!$default_service_name) {
      $definitions = $this->getDefinitions();
      $default_service_name = reset($definitions);
    }

    if ($default_service_name && $this->getDefinition($default_service_name)) {
      return $default_service_name;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDefault($country = NULL) {
    return $this->createInstance($this->getDefaultId(), [
      'country' => $country,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getAddresses($search_term, $country = NULL, $reset = FALSE) {
    // Bail out early if we have no search term.
    if (empty($search_term)) {
      return FALSE;
    }

    $addresses = &drupal_static(__FUNCTION__);

    // Return the statically cached results if present.
    if (isset($addresses[$search_term]) && !$reset) {
      return $addresses[$search_term];
    }

    // If there are no statically cached results, do the search.
    $addresses = [];

    // Get the default service ID.
    $service_id = $this->getDefaultId();

    // Bail out if there is no default service.
    if (!isset($service_id)) {
      return FALSE;
    }
    $service_definition = $this->getDefinition($service_id);

    // Build the cache ID we'll use for this search. Format is
    // service_name:hashed_search_term.
    $addresses_cache_id = $service_id . ':' . Crypt::hashBase64($search_term);

    // Append the country code to the cache ID if present.
    if (!empty($country)) {
      $addresses_cache_id .= ':' . $country;
    }

    // Allow the default service module to alter the cache ID.
    if ($updated_cache_id = \Drupal::moduleHandler()->invoke($service_definition['provider'], 'addressfield_lookup_get_addresses_cache_id_update', [$addresses_cache_id, $country])) {
      $addresses_cache_id = $updated_cache_id;
    }

    // Check the cache bin for the address details.
    if (($cached_addresses = \Drupal::cache('addressfield_lookup_addresses')->get($addresses_cache_id)) && !$reset) {
      // There is cached data so return it.
      $addresses[$search_term] = $cached_addresses->data;
      return $addresses[$search_term];
    }

    // There is no static or Drupal cache data. Do the lookup.
    try {
      // Get the default service object.
      if ($service = $this->getDefault($country)) {
        // Do the search.
        if ($lookup_results = $service->lookup($search_term)) {
          $addresses[$search_term] = $lookup_results;

          // Cache the addresses.
          $cache_length = REQUEST_TIME + \Drupal::config('addressfield_lookup.settings')->get('cache_length');
          \Drupal::cache('addressfield_lookup_addresses')->set($addresses_cache_id, $addresses[$search_term], $cache_length);
        }
        else {
          $addresses[$search_term] = [];
        }

        return $addresses[$search_term];
      }
      else {
        // No service could be instantiated so bail out.
        return FALSE;
      }
    }
    catch (Exception $e) {
      // Failed to get addresses due to an exception, better log it.
      \Drupal::logger('addressfield_lookup')->error('Address lookup failed. Reason: @reason', array('@reason' => $e->getMessage()));
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressDetails($address_id, $reset = FALSE) {
    // Bail out early if we have no address ID.
    if (empty($address_id)) {
      return FALSE;
    }

    $address_details = &drupal_static(__FUNCTION__);

    // Return the statically cached details if present.
    if (isset($address_details[$address_id]) && !$reset) {
      return $address_details[$address_id];
    }

    // If there are no statically details do the retrieval.
    // Get the default service ID.
    $service_id = $this->getDefaultId();

    // Bail out if there is no default service.
    if (!isset($service_id)) {
      return FALSE;
    }
    $service_definition = $this->getDefinition($service_id);

    // Build the cache ID we'll use for this retrieval. Format is
    // service_name:address_id.
    $address_details_cache_id = $service_id . ':' . $address_id;

    // Check the cache bin for the address details.
    if (($cached_address_details = \Drupal::cache('addressfield_lookup_address_details')->get($address_details_cache_id)) && !$reset) {
      // There is cached data so return it.
      $address_details[$address_id] = $cached_address_details->data;
      return $address_details[$address_id];
    }

    $address_details = [];

    // There is no static or Drupal cache data. Do the address retrieval.
    try {
      // Get the default service object.
      if ($service = $this->getDefault()) {
        // Get the address details from the service.
        $address_details[$address_id] = $service->getAddressDetails($address_id);

        // Cache the address details.
        $cache_length = REQUEST_TIME + \Drupal::config('addressfield_lookup.settings')->get('addressfield_lookup_cache_length');
        \Drupal::cache('addressfield_lookup_address_details')->set($address_details_cache_id, $address_details[$address_id], $cache_length);

        return $address_details[$address_id];
      }
      else {
        // No service could be instantiated so bail out.
        return FALSE;
      }
    }
    catch (Exception $e) {
      // Failed to get address details due to an exception, better log it.
      \Drupal::logger('addressfield_lookup')->error('Address details retrieval failed. Reason: @reason', array('@reason' => $e->getMessage()));
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormatUpdates(array &$format, array $address) {
    // Get the default service.
    $service_id = $this->getDefaultId();
    // Bail out if there is no default service.
    if (!isset($service_id)) {
      return FALSE;
    }
    $service_definition = $this->getDefinition($service_id);

    // Invoke the update hook (if it exists) in the default service module.
    if ($format_updates = \Drupal::moduleHandler()->invoke($service_definition['provider'], 'addressfield_lookup_format_update', [$format, $address])) {
      $format = $format_updates;
    }
  }

}
