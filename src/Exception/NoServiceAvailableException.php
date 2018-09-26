<?php

namespace Drupal\addressfield_lookup\Exception;

use Drupal\Component\Plugin\Exception\PluginException;

/**
 * Thrown when there is no address lookup service.
 */
class NoServiceAvailableException extends PluginException {}
