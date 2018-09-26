<?php

namespace Drupal\addressfield_lookup\Plugin\AddressLookup;

use Drupal\addressfield_lookup\AddressLookupInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Base class for AddressLookup plugins.
 */
abstract class AddressLookupBase extends PluginBase implements AddressLookupInterface {

  /**
   * ISO2 country code.
   *
   * Defaults to UK.
   *
   * @var string
   */
  protected $country = 'GB';

  /**
   * Constructs a new CheckoutPaneBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param string $country
   *   (optional) ISO2 code of the country to get addresses in.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $country = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if (!empty($country)) {
      $this->country = $country;
    }
  }

}
