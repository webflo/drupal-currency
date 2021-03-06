<?php

/**
 * @file Contains
 * \Drupal\currency\Plugin\Currency\ExchangeRateProvider\ExchangeRateProviderManagerInterface.
 */

namespace Drupal\currency\Plugin\Currency\ExchangeRateProvider;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\currency\Plugin\Currency\OperationsProviderPluginManagerInterface;

/**
 * Defines an amount formatter plugin manager.
 */
interface ExchangeRateProviderManagerInterface extends PluginManagerInterface, OperationsProviderPluginManagerInterface {

  /**
   * Creates an exchange rate provider.
   *
   * @param string $plugin_id
   *   The id of the plugin being instantiated.
   * @param array $configuration
   *   An array of configuration relevant to the plugin instance.
   *
   * @return \Drupal\currency\Plugin\Currency\ExchangeRateProvider\ExchangeRateProviderInterface
   */
  public function createInstance($plugin_id, array $configuration = array());

}
