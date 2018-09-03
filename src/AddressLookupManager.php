<?php

namespace Drupal\addressfield_lookup;

//use Drupal\Component\Plugin\Exception\PluginException;
//use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages address lookup plugins.
 */
class AddressLookupManager extends DefaultPluginManager {

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

}
