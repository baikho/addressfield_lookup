<?php

namespace Drupal\addressfield_lookup;

use Drupal\addressfield_lookup\Exception\NoServiceAvailableException;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Exception;
use UnexpectedValueException;

/**
 * Manages address lookup plugins.
 */
class AddressLookupManager extends DefaultPluginManager implements AddressLookupManagerInterface {

  /**
   * A list of previously retrieved addresses, listed per search term.
   *
   * @var array
   */
  protected $addresses = [];

  /**
   * A list of previously retrieved address details, listed per address ID.
   *
   * @var array
   */
  protected $addressDetails = [];

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
  public function createInstance($plugin_id, array $configuration = [], $country = NULL) {
    $plugin_definition = $this->getDefinition($plugin_id);
    $plugin_class = DefaultFactory::getPluginClass($plugin_id, $plugin_definition);
    // If the plugin provides a factory method, pass the container to it.
    if (is_subclass_of($plugin_class, ContainerFactoryPluginInterface::class)) {
      $plugin = $plugin_class::create(\Drupal::getContainer(), $configuration, $plugin_id, $plugin_definition, $country);
    }
    else {
      $plugin = new $plugin_class($configuration, $plugin_id, $plugin_definition, $country);
    }

    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultId() {
    $default_service_name = $this->configGet('default_service');

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
    return $this->createInstance($this->getDefaultId(), [], $country);
  }

  /**
   * {@inheritdoc}
   */
  public function getAddresses($search_term, $country = NULL, $reset = FALSE) {
    // Bail out early if we have no search term.
    if (empty($search_term)) {
      throw new UnexpectedValueException('Invalid search term.');
    }

    // Set country cache key.
    $country_cache_key = is_null($country) ? 'default' : $country;

    // Return the statically cached results if present.
    if (isset($this->addresses[$country_cache_key][$search_term]) && !$reset) {
      return $this->addresses[$country_cache_key][$search_term];
    }

    // If there are no statically cached results, do the search.
    $this->addresses[$country_cache_key][$search_term] = [];

    // Get the default service ID.
    $service_id = $this->getDefaultId();

    // Bail out if there is no default service.
    if (!isset($service_id)) {
      throw new NoServiceAvailableException('There is no address lookup service available.');
    }
    $service_definition = $this->getDefinition($service_id);

    // Build the cache ID we'll use for this search. Format is
    // service_name:hashed_search_term.
    $addresses_cache_id = $service_id . ':' . Crypt::hashBase64($search_term);

    // Append the country code to the cache ID if present.
    if (!empty($country)) {
      $addresses_cache_id .= ':' . $country_cache_key;
    }

    // Allow the default service module to alter the cache ID.
    if ($updated_cache_id = $this->moduleHandler->invoke($service_definition['provider'], 'addressfield_lookup_get_addresses_cache_id_update', [$addresses_cache_id, $country])) {
      $addresses_cache_id = $updated_cache_id;
    }

    // Check the cache bin for the address details.
    if (($cached_addresses = $this->getCacheBin('addressfield_lookup_addresses')->get($addresses_cache_id)) && !$reset) {
      // There is cached data so return it.
      $this->addresses[$country_cache_key][$search_term] = $cached_addresses->data;
      return $this->addresses[$country_cache_key][$search_term];
    }

    // There is no static or Drupal cache data. Do the lookup.
    try {
      // Get the default service object.
      if ($service = $this->getDefault($country)) {
        // Do the search.
        if ($lookup_results = $service->lookup($search_term)) {
          $this->addresses[$country_cache_key][$search_term] = $lookup_results;

          // Cache the addresses.
          $cache_length = $this->getRequestTime() + $this->configGet('cache_length');
          $this->getCacheBin('addressfield_lookup_addresses')->set($addresses_cache_id, $this->addresses[$country_cache_key][$search_term], $cache_length);
        }
        else {
          $this->addresses[$country_cache_key][$search_term] = [];
        }

        return $this->addresses[$country_cache_key][$search_term];
      }
      else {
        // No service could be instantiated so bail out.
        throw new NoServiceAvailableException('There is no address lookup service available.');
      }
    }
    catch (Exception $e) {
      // Failed to get addresses due to an exception, better log it.
      $this->getLogger()->error('Address lookup failed. Reason: @reason', array('@reason' => $e->getMessage()));
      throw $e;
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

    // Return the statically cached details if present.
    if (isset($this->addressDetails[$address_id]) && !$reset) {
      return $this->addressDetails[$address_id];
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
    if (($cached_address_details = $this->getCacheBin('addressfield_lookup_address_details')->get($address_details_cache_id)) && !$reset) {
      // There is cached data so return it.
      $this->addressDetails[$address_id] = $cached_address_details->data;
      return $this->addressDetails[$address_id];
    }

    $this->address_details[$address_id] = [];

    // There is no static or Drupal cache data. Do the address retrieval.
    try {
      // Get the default service object.
      if ($service = $this->getDefault()) {
        // Get the address details from the service.
        $this->addressDetails[$address_id] = $service->getAddressDetails($address_id);

        // Cache the address details.
        $cache_length = $this->getRequestTime() + $this->configGet('cache_length');
        $this->getCacheBin('addressfield_lookup_address_details')->set($address_details_cache_id, $this->addressDetails[$address_id], $cache_length);

        return $this->addressDetails[$address_id];
      }
      else {
        // No service could be instantiated so bail out.
        return FALSE;
      }
    }
    catch (Exception $e) {
      // Failed to get address details due to an exception, better log it.
      $this->getLogger()->error('Address details retrieval failed. Reason: @reason', array('@reason' => $e->getMessage()));
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
    if ($format_updates = $this->moduleHandler->invoke($service_definition['provider'], 'addressfield_lookup_format_update', [$format, $address])) {
      $format = $format_updates;
    }
  }

  /**
   * Returns the requested cache bin.
   *
   * @param string $bin
   *   The cache bin for which the cache object should be returned.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface
   *   The cache object associated with the specified bin.
   */
  protected function getCacheBin($bin) {
    return \Drupal::cache($bin);
  }

  /**
   * Returns the logger object for addressfield_lookup channel.
   *
   * @return \Psr\Log\LoggerInterface
   *   The logger for "addressfield_lookup".
   */
  protected function getLogger() {
    return \Drupal::logger('addressfield_lookup');
  }

  /**
   * @todo document.
   */
  protected function getRequestTime() {
    return \Drupal::time()->getRequestTime();
  }

  /**
   * @todo document.
   */
  protected function configGet($key) {
    return \Drupal::config('addressfield_lookup.settings')->get($key);
  }

}
