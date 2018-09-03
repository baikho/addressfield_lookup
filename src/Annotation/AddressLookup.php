<?php

namespace Drupal\addressfield_lookup\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the address lookup plugin annotation object.
 *
 * Plugin namespace: Plugin\AddressLookup
 *
 * @Annotation
 */
class AddressLookup extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the service.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The brief description of the address field lookup service.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

  /**
   * The route of the configuration form for this service.
   *
   * The route will be used as an inline link on the address field lookup module
   * configuration form.
   *
   * @var string
   */
  public $route;

  /**
   * Example value.
   *
   * An example value that will be used to test the status of connectivity to
   * the service.
   *
   * @var string
   */
  public $test_data;

}
